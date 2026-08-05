<?php
/**
 * Plugin Name:       WooCommerce SKU Image Updater
 * Plugin URI:        https://imkthis.com
 * Description:       Bulk-replace WooCommerce product featured images by matching uploaded files to product SKUs (simple &amp; variable/variation products).
 * Version:           1.0.0
 * Author:            I Make This
 * Author URI:        https://imkthis.com
 * Text Domain:       sku-image-updater
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 * WC tested up to:      9.0
 *
 * @package Sku_Image_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'SIU_VERSION', '1.0.0' );
define( 'SIU_PLUGIN_FILE', __FILE__ );
define( 'SIU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstraps the plugin once all plugins are loaded, so we can
 * safely check whether WooCommerce is active.
 */
function siu_bootstrap() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'siu_missing_woocommerce_notice' );
		return;
	}

	require_once SIU_PLUGIN_DIR . 'includes/class-siu-admin.php';
	require_once SIU_PLUGIN_DIR . 'includes/class-siu-ajax.php';

	new SIU_Admin();
	new SIU_Ajax();
}
add_action( 'plugins_loaded', 'siu_bootstrap' );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function siu_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'WooCommerce SKU Image Updater requires WooCommerce to be installed and active.', 'sku-image-updater' );
	echo '</p></div>';
}
