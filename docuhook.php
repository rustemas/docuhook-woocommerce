<?php
/**
 * Plugin Name: DocuHook
 * Description: Connect WordPress / WooCommerce to the DocuHook Webhook to PDF API. Merge order JSON into HTML invoice templates via your API key.
 * Version: 1.0.0
 * Author: DocuHook
 * Author URI: https://docuhook.com
 * Plugin URI: https://github.com/rustemas/docuhook-woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: docuhook
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DOCUHOOK_VERSION', '1.0.0');
define('DOCUHOOK_PLUGIN_FILE', __FILE__);
define('DOCUHOOK_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once DOCUHOOK_PLUGIN_DIR . 'includes/class-client.php';
require_once DOCUHOOK_PLUGIN_DIR . 'includes/class-admin.php';
require_once DOCUHOOK_PLUGIN_DIR . 'includes/functions.php';

add_action('plugins_loaded', static function () {
    DocuHook_Admin::init();
});
