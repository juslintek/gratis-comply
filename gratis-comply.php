<?php
/**
 * Plugin Name: Gratis Comply
 * Plugin URI: https://github.com/juslintek/gratis-comply
 * Description: GDPR/CCPA compliance: cookie consent banner, privacy policy generator, and data export/erasure requests.
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

// Consent Mode v2 default + controller — as early as possible in <head>.
add_action('wp_head', [\Gratis\Comply\CookieConsent::class, 'render_head'], 0);
add_action('wp_footer', [\Gratis\Comply\CookieConsent::class, 'render']);
add_action('rest_api_init', [\Gratis\Comply\DataRequests::class, 'register']);

// Admin settings page + privacy-policy generator action.
add_action('admin_menu', [\Gratis\Comply\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Comply\Settings::class, 'register_settings']);
\Gratis\Comply\Settings::register_actions();

if (! function_exists('gratis_comply_consent_script')) {
    /**
     * Template tag: build a consent-gated <script> placeholder.
     *
     * The script stays inert until the visitor accepts $category, then the
     * front-end controller activates it. Echo the return value in your theme
     * or plugin output. Wraps {@see \Gratis\Comply\Consent::script_tag()}.
     *
     * @param string               $category Category slug ('analytics', 'marketing', ...).
     * @param string               $code     Inline JavaScript (ignored when 'src' is given).
     * @param array<string,string> $args     Optional attributes; 'src' defers an external script.
     * @return string Escaped, ready-to-output HTML.
     */
    function gratis_comply_consent_script(string $category, string $code = '', array $args = []): string {
        return \Gratis\Comply\Consent::script_tag($category, $code, $args);
    }
}
