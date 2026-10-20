=== DocuHook ===
Contributors: docuhook
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce / WordPress connector for the DocuHook Webhook to PDF API.

== Description ==

Connect WordPress and WooCommerce to DocuHook (https://docuhook.com) to merge order JSON into HTML invoice templates and produce PDFs.

* Settings: API URL and API key
* `docuhook_render( $data, $html, $args )` helper for themes and plugins
* WooCommerce order.completed → invoice examples in this repo
* Admin test render button
* Sync or async (job) mode

Get an API key at https://docuhook.com/register
GitHub: https://github.com/rustemas/docuhook-woocommerce

== Installation ==

1. Upload the `docuhook` folder to `/wp-content/plugins/` or install from the zip
2. Activate the DocuHook plugin
3. Go to Settings → DocuHook and paste your API URL and API key
4. Click Send test render

== Frequently Asked Questions ==

= Do I need a DocuHook account? =

Yes. Register at https://docuhook.com/register and create an API key under Account.

= Does this generate PDF files inside WordPress? =

The plugin calls the DocuHook API, which merges your JSON and HTML template. Use the returned HTML or your DocuHook plan features for PDF delivery.

== Changelog ==

= 1.0.0 =
* Initial release
