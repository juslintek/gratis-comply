<?php declare(strict_types=1);

namespace Gratis\Comply;

defined('ABSPATH') || exit;

/**
 * Admin settings + tools for Gratis Comply.
 *
 * Configures the cookie banner and provides a one-click privacy-policy page
 * generator plus a consent-log summary.
 */
final class Settings {

    private const GROUP = 'gratis_comply_settings_group';
    private const PAGE  = 'gratis-comply';

    private const OPT_ENABLED = 'gratis_comply_banner_enabled';
    private const OPT_TEXT    = 'gratis_comply_banner_text';
    private const OPT_PRIVACY = 'gratis_comply_privacy_url';

    private const DEFAULT_TEXT = 'We use essential cookies only. No tracking.';

    /** Register the settings submenu page. */
    public static function register_menu(): void {
        add_options_page(
            __('Gratis Comply', 'gratis-comply'),
            __('Gratis Comply', 'gratis-comply'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register options with typed sanitisers. */
    public static function register_settings(): void {
        register_setting(self::GROUP, self::OPT_ENABLED, [
            'type'              => 'boolean',
            'sanitize_callback' => [__CLASS__, 'sanitize_bool'],
            'default'           => true,
        ]);
        register_setting(self::GROUP, self::OPT_TEXT, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => self::DEFAULT_TEXT,
        ]);
        register_setting(self::GROUP, self::OPT_PRIVACY, [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => '',
        ]);
    }

    /** Register the privacy-policy generator admin-post handler. */
    public static function register_actions(): void {
        add_action('admin_post_gratis_comply_generate_policy', [__CLASS__, 'handle_generate_policy']);
    }

    /** Normalise a checkbox value to a strict bool. */
    public static function sanitize_bool($value): bool {
        return (bool) $value;
    }

    /** Whether the cookie banner is enabled. */
    public static function bannerEnabled(): bool {
        return (bool) get_option(self::OPT_ENABLED, true);
    }

    /** Configured banner text. */
    public static function bannerText(): string {
        $text = (string) get_option(self::OPT_TEXT, self::DEFAULT_TEXT);
        return $text !== '' ? $text : self::DEFAULT_TEXT;
    }

    /** Configured privacy-policy URL (falls back to WP's privacy page). */
    public static function privacyUrl(): string {
        $url = (string) get_option(self::OPT_PRIVACY, '');
        if ($url === '') {
            $url = (string) get_privacy_policy_url();
        }
        return $url;
    }

    /** Generate a draft privacy-policy page (nonce + capability gated). */
    public static function handle_generate_policy(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to do that.', 'gratis-comply'));
        }
        check_admin_referer('gratis_comply_generate_policy');

        $existing = (int) get_option('wp_page_for_privacy_policy', 0);
        if ($existing > 0 && get_post($existing)) {
            $page_id = $existing;
        } else {
            $page_id = wp_insert_post([
                'post_type'    => 'page',
                'post_status'  => 'draft',
                'post_title'   => __('Privacy Policy', 'gratis-comply'),
                'post_content' => self::policyTemplate(),
            ]);
            if (! is_wp_error($page_id) && $page_id > 0) {
                update_option('wp_page_for_privacy_policy', $page_id);
            }
        }

        $ok = (! is_wp_error($page_id) && $page_id > 0) ? '1' : '0';
        wp_safe_redirect(add_query_arg(
            ['page' => self::PAGE, 'gratis_comply_policy' => $ok],
            admin_url('options-general.php')
        ));
        exit;
    }

    /** Boilerplate privacy-policy body. */
    private static function policyTemplate(): string {
        $name = get_bloginfo('name');
        return sprintf(
            /* translators: %s: site name. */
            __('This privacy policy describes how %s collects, uses, and protects your personal information. We use essential cookies only and do not track visitors across sites. You may request an export or erasure of your personal data at any time.', 'gratis-comply'),
            $name
        );
    }

    /** Render the admin screen. */
    public static function render_page(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-comply'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Gratis Comply', 'gratis-comply') . '</h1>';

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
        if (isset($_GET['gratis_comply_policy'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag.
            $ok    = '1' === sanitize_key(wp_unslash($_GET['gratis_comply_policy']));
            $class = $ok ? 'notice-success' : 'notice-error';
            $text  = $ok
                ? __('Privacy policy page is ready (saved as a draft).', 'gratis-comply')
                : __('Could not create the privacy policy page.', 'gratis-comply');
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($text) . '</p></div>';
        }

        self::render_options_form();
        self::render_policy_tool();
        self::render_consent_summary();

        echo '</div>';
    }

    /** Cookie-banner settings form. */
    private static function render_options_form(): void {
        echo '<h2>' . esc_html__('Cookie banner', 'gratis-comply') . '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Enable banner', 'gratis-comply') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_ENABLED) . '" value="1"' . checked(self::bannerEnabled(), true, false) . '> ' . esc_html__('Show the cookie consent banner', 'gratis-comply') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gc_text">' . esc_html__('Banner text', 'gratis-comply') . '</label></th><td>';
        echo '<input type="text" id="gc_text" class="large-text" name="' . esc_attr(self::OPT_TEXT) . '" value="' . esc_attr(self::bannerText()) . '">';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gc_privacy">' . esc_html__('Privacy policy URL', 'gratis-comply') . '</label></th><td>';
        echo '<input type="url" id="gc_privacy" class="regular-text" name="' . esc_attr(self::OPT_PRIVACY) . '" value="' . esc_attr((string) get_option(self::OPT_PRIVACY, '')) . '">';
        echo '<p class="description">' . esc_html__('Leave blank to use the WordPress privacy page.', 'gratis-comply') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';
        submit_button(__('Save settings', 'gratis-comply'));
        echo '</form>';
    }

    /** Privacy-policy generator button. */
    private static function render_policy_tool(): void {
        echo '<h2>' . esc_html__('Privacy policy generator', 'gratis-comply') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('gratis_comply_generate_policy');
        echo '<input type="hidden" name="action" value="gratis_comply_generate_policy">';
        echo '<p class="description">' . esc_html__('Create (or reuse) a draft privacy policy page and set it as the site privacy page.', 'gratis-comply') . '</p>';
        submit_button(__('Generate privacy policy', 'gratis-comply'), 'secondary', 'submit', false);
        echo '</form>';
    }

    /** Consent-log summary. */
    private static function render_consent_summary(): void {
        $log = get_option('gratis_consent_log', []);
        $log = is_array($log) ? $log : [];
        $accept = 0;
        $reject = 0;
        foreach ($log as $entry) {
            if (($entry['choice'] ?? '') === 'accept') {
                ++$accept;
            } elseif (($entry['choice'] ?? '') === 'reject') {
                ++$reject;
            }
        }

        echo '<h2>' . esc_html__('Consent log', 'gratis-comply') . '</h2>';
        echo '<p>';
        printf(
            /* translators: 1: accept count, 2: reject count, 3: total. */
            esc_html__('Accepted: %1$s — Rejected: %2$s — Total recorded: %3$s', 'gratis-comply'),
            esc_html(number_format_i18n($accept)),
            esc_html(number_format_i18n($reject)),
            esc_html(number_format_i18n(count($log)))
        );
        echo '</p>';
    }
}
