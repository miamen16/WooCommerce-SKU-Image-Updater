=== Aura SKU Image Updater for WooCommerce ===
Contributors: imakethis, mibrahim1995
Tags: woocommerce, products, sku, images, bulk edit
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bulk-replace WooCommerce product featured images by matching uploaded files to product SKUs.

== Description ==

Upload one or more images from the WooCommerce admin, type in the SKU for each one, and the plugin will:

* Find the matching product automatically by SKU (simple, variable parent, or a specific variation).
* Replace the product's (or variation's) featured image.
* Regenerate all registered WordPress image sizes for the new image.
* Optionally delete the old featured image, but only if no other product still uses it.
* Show a live success/error log, with no page reloads (AJAX upload).

== Installation ==

1. Upload the `aura-sku-image-updater-for-woocommerce` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **WooCommerce → Aura SKU Image Updater**.

== Usage ==

1. For each image, click "Choose File" and enter the matching product SKU (the SKU field auto-fills from the filename, but is fully editable).
2. Click "+ Add Row" to queue more images.
3. Leave "Delete old featured image" checked if you want the previous image removed automatically once it's no longer used anywhere.
4. Click "Upload & Update" and watch the log below the table.

== Changelog ==

= 1.0.1 =
* Updated plugin name and slug for WordPress.org review.
* Added WooCommerce plugin dependency declaration.
* Added unique Aura SKU prefixes to plugin identifiers.

= 1.0.0 =
* Initial release.
