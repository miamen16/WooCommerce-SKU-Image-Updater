<?php
/**
 * Handles the AJAX upload + SKU matching + featured image replacement.
 *
 * @package Sku_Image_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SIU_Ajax {

	/**
	 * Hook the AJAX action (admin-only, logged-in users only).
	 */
	public function __construct() {
		add_action( 'wp_ajax_siu_upload_image', array( $this, 'handle_upload' ) );
	}

	/**
	 * Process a single image + SKU pair.
	 */
	public function handle_upload() {
		check_ajax_referer( 'siu_upload_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'sku-image-updater' ) ) );
		}

		$sku        = isset( $_POST['sku'] ) ? sanitize_text_field( wp_unslash( $_POST['sku'] ) ) : '';
		$delete_old = ! empty( $_POST['delete_old'] );

		if ( '' === $sku ) {
			wp_send_json_error( array( 'message' => __( 'SKU is required.', 'sku-image-updater' ) ) );
		}

		if ( empty( $_FILES['siu_image'] ) || UPLOAD_ERR_OK !== $_FILES['siu_image']['error'] ) {
			wp_send_json_error( array( 'message' => __( 'No valid image file was received.', 'sku-image-updater' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below via wp_check_filetype_and_ext.
		$file = $_FILES['siu_image'];

		$filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
			wp_send_json_error( array( 'message' => __( 'The uploaded file is not a supported image type (jpg, png, gif, webp).', 'sku-image-updater' ) ) );
		}

		$product_id = wc_get_product_id_by_sku( $sku );
		if ( ! $product_id ) {
			wp_send_json_error(
				array(
					/* translators: %s: SKU that was searched for. */
					'message' => sprintf( __( 'No product found with SKU "%s".', 'sku-image-updater' ), esc_html( $sku ) ),
				)
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product could not be loaded.', 'sku-image-updater' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Own image id (no fallback to parent), so we know exactly what to delete.
		$old_image_id = (int) $product->get_image_id( 'edit' );

		// Attach the new media item to the parent product post for variations,
		// so it shows in the correct product's media/gallery context.
		$attach_to = $product->get_parent_id() ? $product->get_parent_id() : $product_id;

		$attachment_id = media_handle_upload( 'siu_image', $attach_to );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error(
				array(
					/* translators: %s: underlying WordPress upload error message. */
					'message' => sprintf( __( 'Upload failed: %s', 'sku-image-updater' ), $attachment_id->get_error_message() ),
				)
			);
		}

		$product->set_image_id( $attachment_id );
		$product->save();

		$deleted_old = false;
		if ( $delete_old && $old_image_id && $old_image_id !== (int) $attachment_id ) {
			if ( $this->attachment_unused_elsewhere( $old_image_id, $product_id ) ) {
				wp_delete_attachment( $old_image_id, true );
				$deleted_old = true;
			}
		}

		$message = sprintf(
			/* translators: %s: product name. */
			__( 'Featured image updated for "%s".', 'sku-image-updater' ),
			$product->get_name()
		);

		if ( $delete_old ) {
			$message .= $old_image_id
				? ( $deleted_old
					? ' ' . __( 'Old image deleted.', 'sku-image-updater' )
					: ' ' . __( 'Old image kept (still used by another product).', 'sku-image-updater' ) )
				: ' ' . __( 'No previous image to delete.', 'sku-image-updater' );
		}

		wp_send_json_success(
			array(
				'message'      => $message,
				'product_name' => $product->get_name(),
				'product_type' => $product->get_type(),
				'edit_link'    => get_edit_post_link( $attach_to, '' ),
				'thumbnail'    => wp_get_attachment_image_url( $attachment_id, 'thumbnail' ),
			)
		);
	}

	/**
	 * Check whether an attachment is still set as another product's
	 * featured/variation image before it gets deleted.
	 *
	 * @param int $attachment_id      Attachment to check.
	 * @param int $exclude_product_id Product ID that just stopped using it.
	 * @return bool True if no other product references this attachment.
	 */
	private function attachment_unused_elsewhere( $attachment_id, $exclude_product_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d AND post_id != %d",
				$attachment_id,
				$exclude_product_id
			)
		);

		return 0 === (int) $count;
	}
}
