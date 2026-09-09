<?php

/**
 * Handles AJAX upload, SKU matching, and featured image replacement.
 *
 * @package Aura_Sku_Image_Updater
 */

if (! defined('ABSPATH')) {
	exit;
}

class AURASKU_Ajax
{
	public function __construct()
	{
		add_action('wp_ajax_aurasku_upload_image', array($this, 'handle_upload'));
	}

	public function handle_upload()
	{
		check_ajax_referer('aurasku_upload_nonce', 'nonce');

		if (! current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'aura-sku-image-updater-for-woocommerce')));
		}

		$sku        = isset($_POST['sku']) ? sanitize_text_field(wp_unslash($_POST['sku'])) : '';
		$delete_old = ! empty($_POST['delete_old']);

		if ('' === $sku) {
			wp_send_json_error(array('message' => __('SKU is required.', 'aura-sku-image-updater-for-woocommerce')));
		}

		if (
			empty($_FILES['aurasku_image'])
			|| ! isset($_FILES['aurasku_image']['error'])
			|| UPLOAD_ERR_OK !== $_FILES['aurasku_image']['error']
		) {
			wp_send_json_error(
				array(
					'message' => __('No valid image file was received.', 'aura-sku-image-updater-for-woocommerce'),
				)
			);
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Uploaded file data is validated with wp_check_filetype_and_ext() before use.
		$file = $_FILES['aurasku_image'];
		$filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
		$allowed_mimes = array(
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		);

		if (empty($filetype['type']) || ! in_array($filetype['type'], $allowed_mimes, true)) {
			wp_send_json_error(array('message' => __('The uploaded file is not a supported image type (jpg, png, gif, webp).', 'aura-sku-image-updater-for-woocommerce')));
		}

		$product_id = wc_get_product_id_by_sku($sku);
		if (! $product_id) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: Product SKU. */
						__('No product found with SKU "%s".', 'aura-sku-image-updater-for-woocommerce'),
						esc_html($sku)
					),
				)
			);
		}

		$product = wc_get_product($product_id);
		if (! $product) {
			wp_send_json_error(array('message' => __('Product could not be loaded.', 'aura-sku-image-updater-for-woocommerce')));
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$old_image_id = (int) $product->get_image_id('edit');
		$attach_to = $product->get_parent_id() ? $product->get_parent_id() : $product_id;
		$attachment_id = media_handle_upload('aurasku_image', $attach_to);

		if (is_wp_error($attachment_id)) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %s: Error message returned by the WordPress media upload handler. */
						__('Upload failed: %s', 'aura-sku-image-updater-for-woocommerce'),
						$attachment_id->get_error_message()
					),
				)
			);
		}

		$product->set_image_id($attachment_id);
		$product->save();

		$deleted_old = false;
		if ($delete_old && $old_image_id && $old_image_id !== (int) $attachment_id && $this->attachment_unused_elsewhere($old_image_id, $product_id)) {
			wp_delete_attachment($old_image_id, true);
			$deleted_old = true;
		}

		$message = sprintf(
			/* translators: %s: Product name. */
			__('Featured image updated for "%s".', 'aura-sku-image-updater-for-woocommerce'),
			$product->get_name()
		);
		if ($delete_old) {
			$message .= $old_image_id
				? ($deleted_old ? ' ' . __('Old image deleted.', 'aura-sku-image-updater-for-woocommerce') : ' ' . __('Old image kept (still used by another product).', 'aura-sku-image-updater-for-woocommerce'))
				: ' ' . __('No previous image to delete.', 'aura-sku-image-updater-for-woocommerce');
		}

		wp_send_json_success(
			array(
				'message'      => $message,
				'product_name' => $product->get_name(),
				'product_type' => $product->get_type(),
				'edit_link'    => get_edit_post_link($attach_to, ''),
				'thumbnail'    => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
			)
		);
	}

	private function attachment_unused_elsewhere($attachment_id, $exclude_product_id)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct meta lookup is required to safely determine whether the attachment is still used by another product.
		$thumbnail_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(post_id)
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_thumbnail_id'
				AND meta_value = %d
				AND post_id != %d",
				$attachment_id,
				$exclude_product_id
			)
		);

		if (0 < (int) $thumbnail_count) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct meta lookup is required to inspect WooCommerce's comma-separated product gallery attachment IDs.
		$gallery_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(post_id)
				FROM {$wpdb->postmeta}
				WHERE meta_key = '_product_image_gallery'
				AND post_id != %d
				AND FIND_IN_SET(%d, meta_value)",
				$exclude_product_id,
				$attachment_id
			)
		);

		return 0 === (int) $gallery_count;
	}
}
