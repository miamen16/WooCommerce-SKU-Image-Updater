<?php
/**
 * Admin screen for the SKU Image Updater.
 *
 * @package Sku_Image_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SIU_Admin {

	/**
	 * Hook the admin menu and asset loading.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the admin page under WooCommerce.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'SKU Image Updater', 'sku-image-updater' ),
			__( 'SKU Image Updater', 'sku-image-updater' ),
			'manage_woocommerce',
			'sku-image-updater',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Only load JS/CSS on our own admin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_sku-image-updater' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'siu-admin',
			SIU_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SIU_VERSION
		);

		wp_enqueue_script(
			'siu-admin',
			SIU_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SIU_VERSION,
			true
		);

		wp_localize_script(
			'siu-admin',
			'SIU_Data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'siu_upload_nonce' ),
				'i18n'     => array(
					'skipped'    => __( 'Skipped (missing file or SKU).', 'sku-image-updater' ),
					'processing' => __( 'Processing SKU', 'sku-image-updater' ),
					'requestFailed' => __( 'Request failed - check your connection and try again.', 'sku-image-updater' ),
					'noRows'    => __( 'Add at least one image + SKU row first.', 'sku-image-updater' ),
					'uploading' => __( 'Processing…', 'sku-image-updater' ),
					'submit'    => __( 'Upload & Update', 'sku-image-updater' ),
				),
			)
		);
	}

	/**
	 * Render the admin page markup. Rows are built client-side in JS.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap siu-wrap">
			<h1><?php esc_html_e( 'WooCommerce SKU Image Updater', 'sku-image-updater' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Upload one or more images, enter the SKU that matches each one, and the plugin will find the product (simple, variable, or a specific variation), replace its featured image, and regenerate WordPress image sizes automatically.', 'sku-image-updater' ); ?>
			</p>

			<label class="siu-delete-old-label">
				<input type="checkbox" id="siu-delete-old" checked="checked" />
				<?php esc_html_e( 'Delete the old featured image after replacing it (skipped automatically if that image is still used by another product)', 'sku-image-updater' ); ?>
			</label>

			<table class="widefat striped siu-table" id="siu-table">
				<thead>
					<tr>
						<th style="width:35%;"><?php esc_html_e( 'Image file', 'sku-image-updater' ); ?></th>
						<th style="width:25%;"><?php esc_html_e( 'SKU', 'sku-image-updater' ); ?></th>
						<th style="width:15%;"><?php esc_html_e( 'Preview', 'sku-image-updater' ); ?></th>
						<th style="width:25%;"><?php esc_html_e( 'Row action', 'sku-image-updater' ); ?></th>
					</tr>
				</thead>
				<tbody id="siu-rows-body"></tbody>
			</table>

			<p class="siu-actions">
				<button type="button" id="siu-add-row" class="button">
					<?php esc_html_e( '+ Add Row', 'sku-image-updater' ); ?>
				</button>
				<button type="button" id="siu-submit" class="button button-primary">
					<?php esc_html_e( 'Upload & Update', 'sku-image-updater' ); ?>
				</button>
			</p>

			<h2><?php esc_html_e( 'Log', 'sku-image-updater' ); ?></h2>
			<div id="siu-log" class="siu-log" aria-live="polite"></div>
		</div>
		<?php
	}
}
