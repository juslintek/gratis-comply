<?php
/**
 * Plugin Name: Gratis Comply
 * Plugin URI: https://github.com/juslintek/gratis-comply
 * Description: GDPR/CCPA compliance: cookie consent banner, privacy policy generator, and data export/erasure requests. Replaces Complianz ($149/yr).
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-comply
 */

defined('ABSPATH') || exit;

define('GRATIS_COMPLY_VERSION', '1.0.0');
define('GRATIS_COMPLY_FILE', __FILE__);
define('GRATIS_COMPLY_DIR', plugin_dir_path(__FILE__));
define('GRATIS_COMPLY_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('wp_footer', [\Gratis\Comply\CookieConsent::class, 'render']);
add_action('rest_api_init', [\Gratis\Comply\DataRequests::class, 'register']);

// Admin settings page + privacy-policy generator action.
add_action('admin_menu', [\Gratis\Comply\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Comply\Settings::class, 'register_settings']);
\Gratis\Comply\Settings::register_actions();
