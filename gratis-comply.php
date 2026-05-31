<?php
/**
 * Plugin Name: Gratis Comply
 * Description: GDPR/CCPA compliance: cookie consent, privacy policy generator, data requests. Replaces Complianz ($149/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';
add_action('wp_footer', [\Gratis\Comply\CookieConsent::class, 'render']);
add_action('rest_api_init', [\Gratis\Comply\DataRequests::class, 'register']);
