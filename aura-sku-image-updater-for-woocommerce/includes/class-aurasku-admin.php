<?php
/**
 * Admin screen for Aura SKU Image Updater for WooCommerce.
 *
 * @package Aura_Sku_Image_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AURASKU_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Aura SKU Image Updater', 'aura-sku-image-updater-for-woocommerce' ),
			__( 'Aura SKU Image Updater', 'aura-sku-image-updater-for-woocommerce' ),
			'manage_woocommerce',
			'aura-sku-image-updater',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_aura-sku-image-updater' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'aurasku-admin', AURASKU_PLUGIN_URL . 'assets/css/admin.css', array(), AURASKU_VERSION );
		wp_enqueue_script( 'aurasku-admin', AURASKU_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), AURASKU_VERSION, true );

		wp_localize_script(
			'aurasku-admin',
			'AURASKU_Data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'aurasku_upload_nonce' ),
				'i18n'     => array(
					'skipped'       => __( 'Skipped (missing file or SKU).', 'aura-sku-image-updater-for-woocommerce' ),
					'processing'    => __( 'Processing SKU', 'aura-sku-image-updater-for-woocommerce' ),
					'requestFailed' => __( 'Request failed - check your connection and try again.', 'aura-sku-image-updater-for-woocommerce' ),
					'noRows'        => __( 'Add at least one image + SKU row first.', 'aura-sku-image-updater-for-woocommerce' ),
					'uploading'     => __( 'Processing…', 'aura-sku-image-updater-for-woocommerce' ),
					'submit'        => __( 'Upload & Update', 'aura-sku-image-updater-for-woocommerce' ),
				),
			)
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap aurasku-wrap">
			<h1><?php esc_html_e( 'Aura SKU Image Updater for WooCommerce', 'aura-sku-image-updater-for-woocommerce' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Upload one or more images, enter the SKU that matches each one, and the plugin will find the product (simple, variable, or a specific variation), replace its featured image, and regenerate WordPress image sizes automatically.', 'aura-sku-image-updater-for-woocommerce' ); ?>
			</p>
			<label class="aurasku-delete-old-label">
				<input type="checkbox" id="aurasku-delete-old" checked="checked" />
				<?php esc_html_e( 'Delete the old featured image after replacing it (skipped automatically if that image is still used by another product)', 'aura-sku-image-updater-for-woocommerce' ); ?>
			</label>
			<table class="widefat striped aurasku-table" id="aurasku-table">
				<thead><tr>
					<th style="width:35%;"><?php esc_html_e( 'Image file', 'aura-sku-image-updater-for-woocommerce' ); ?></th>
					<th style="width:25%;"><?php esc_html_e( 'SKU', 'aura-sku-image-updater-for-woocommerce' ); ?></th>
					<th style="width:15%;"><?php esc_html_e( 'Preview', 'aura-sku-image-updater-for-woocommerce' ); ?></th>
					<th style="width:25%;"><?php esc_html_e( 'Row action', 'aura-sku-image-updater-for-woocommerce' ); ?></th>
				</tr></thead>
				<tbody id="aurasku-rows-body"></tbody>
			</table>
			<p class="aurasku-actions">
				<button type="button" id="aurasku-add-row" class="button"><?php esc_html_e( '+ Add Row', 'aura-sku-image-updater-for-woocommerce' ); ?></button>
				<button type="button" id="aurasku-submit" class="button button-primary"><?php esc_html_e( 'Upload & Update', 'aura-sku-image-updater-for-woocommerce' ); ?></button>
			</p>
			<h2><?php esc_html_e( 'Log', 'aura-sku-image-updater-for-woocommerce' ); ?></h2>
			<div id="aurasku-log" class="aurasku-log" aria-live="polite"></div>
		</div>
		<?php
	}
}
