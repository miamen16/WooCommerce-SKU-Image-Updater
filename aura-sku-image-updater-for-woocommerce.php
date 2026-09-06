<?php

/**
 * Plugin Name:       Aura SKU Image Updater for WooCommerce
 * Plugin URI:        https://github.com/miamen16/aura-sku-image-updater-for-woocommerce
 * Description:       Bulk-replace WooCommerce product featured images by matching uploaded files to product SKUs (simple & variable/variation products).
 * Version:           1.0.2
 * Author:            Aura Systems
 * Author URI:        https://www.aura.llc/
 * Text Domain:       aura-sku-image-updater-for-woocommerce
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * WC requires at least: 6.0
 * WC tested up to:      9.0
 *
 * @package Aura_Sku_Image_Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AURASKU_VERSION', '1.0.2' );
define( 'AURASKU_PLUGIN_FILE', __FILE__ );
define( 'AURASKU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AURASKU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function aurasku_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'aurasku_missing_woocommerce_notice' );
		return;
	}

	require_once AURASKU_PLUGIN_DIR . 'includes/class-aurasku-admin.php';
	require_once AURASKU_PLUGIN_DIR . 'includes/class-aurasku-ajax.php';

	new AURASKU_Admin();
	new AURASKU_Ajax();
}
add_action( 'plugins_loaded', 'aurasku_bootstrap' );

function aurasku_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p>';
	esc_html_e( 'Aura SKU Image Updater for WooCommerce requires WooCommerce to be installed and active.', 'aura-sku-image-updater-for-woocommerce' );
	echo '</p></div>';
}
