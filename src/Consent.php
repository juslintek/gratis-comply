<?php declare(strict_types=1);

namespace Gratis\Comply;

defined('ABSPATH') || exit;

/**
 * Shared consent model: categories, Google Consent Mode v2 signal mapping,
 * the default (all-denied) state, and the script-gating helper.
 *
 * Kept separate from the front-end renderer so the renderer, the REST
 * collector and the admin summary all agree on the same category set without
 * duplicating it.
 */
final class Consent {

    /**
     * Consent categories shown in the banner.
     *
     * "Necessary" is always on (cannot be toggled off). The set is filterable
     * so sites can rename/extend categories; pair any new non-essential
     * category with a Consent Mode mapping via {@see Consent::consentModeMap()}.
     *
     * @return array<string,array{label:string,desc:string,required:bool}>
     */
    public static function categories(): array {
        return apply_filters('gratis_comply_consent_categories', [
            'necessary' => [
                'label'    => __('Necessary', 'gratis-comply'),
                'desc'     => __('Required for the site to work. Always on.', 'gratis-comply'),
                'required' => true,
            ],
            'analytics' => [
                'label'    => __('Analytics', 'gratis-comply'),
                'desc'     => __('Helps us understand how the site is used.', 'gratis-comply'),
                'required' => false,
            ],
            'marketing' => [
                'label'    => __('Marketing', 'gratis-comply'),
                'desc'     => __('Used to deliver and measure advertising.', 'gratis-comply'),
                'required' => false,
            ],
        ]);
    }

    /**
     * Map each non-essential category to the Google Consent Mode v2 signals it
     * grants when accepted. `security_storage` is never listed here — it is
     * always granted (see {@see Consent::defaultSignals()}).
     *
     * @return array<string,string[]>
     */
    public static function consentModeMap(): array {
        return apply_filters('gratis_comply_consent_mode_map', [
            'analytics' => ['analytics_storage'],
            'marketing' => [
                'ad_storage',
                'ad_user_data',
                'ad_personalization',
                'personalization_storage',
                'functionality_storage',
            ],
        ]);
    }

    /**
     * The Consent Mode v2 default: every mapped signal denied, plus
     * `security_storage` granted. Derived from the map so a filtered map stays
     * consistent between the default and the post-consent update.
     *
     * @return array<string,string> Signal => 'granted'|'denied'.
     */
    public static function defaultSignals(): array {
        $signals = ['security_storage' => 'granted'];
        foreach (self::consentModeMap() as $list) {
            foreach ($list as $signal) {
                $signals[$signal] = 'denied';
            }
        }
        return $signals;
    }

    /**
     * Slugs of the non-essential (user-toggleable) categories.
     *
     * @return string[]
     */
    public static function nonEssentialSlugs(): array {
        $slugs = [];
        foreach (self::categories() as $slug => $cat) {
            if (empty($cat['required'])) {
                $slugs[] = (string) $slug;
            }
        }
        return $slugs;
    }

    /**
     * Build a consent-gated <script> placeholder.
     *
     * The returned tag is inert (`type="text/plain"`) until the visitor accepts
     * the given category, at which point the front-end controller rewrites it
     * into a live, executing <script>. Returns ready-to-output, escaped HTML.
     *
     * Inline JS:
     *     echo \Gratis\Comply\Consent::script_tag( 'analytics', 'console.log("GA on");' );
     * External src:
     *     echo \Gratis\Comply\Consent::script_tag(
     *         'marketing', '', [ 'src' => 'https://example.com/pixel.js' ]
     *     );
     *
     * @param string               $category Category slug (e.g. 'analytics', 'marketing').
     * @param string               $code     Inline JavaScript. Ignored when 'src' is given.
     *                                        Output verbatim except a literal `</script` is
     *                                        neutralised; the caller owns its contents.
     * @param array<string,string> $args     Optional extra attributes. 'src' becomes the
     *                                        deferred `data-src`; all other values are
     *                                        attribute-escaped. Reserved names are ignored.
     * @return string Escaped HTML, or '' when the category slug is empty.
     */
    public static function script_tag(string $category, string $code = '', array $args = []): string {
        $category = sanitize_key($category);
        if ($category === '') {
            return '';
        }

        $reserved = ['type', 'src', 'data-src', 'data-gratis-consent'];
        $attrs    = ' type="text/plain" data-gratis-consent="' . esc_attr($category) . '"';

        $src = isset($args['src']) ? esc_url((string) $args['src']) : '';
        if ($src !== '') {
            $attrs .= ' data-src="' . esc_attr($src) . '"';
        }

        foreach ($args as $name => $value) {
            $name = strtolower((string) preg_replace('/[^A-Za-z0-9_-]/', '', (string) $name));
            if ($name === '' || in_array($name, $reserved, true)) {
                continue;
            }
            $attrs .= ' ' . $name . '="' . esc_attr((string) $value) . '"';
        }

        if ($src !== '') {
            return '<script' . $attrs . '></script>';
        }

        // Inert payload: stop a literal closing tag from breaking out early.
        $code = str_ireplace('</script', '<\/script', $code);
        return '<script' . $attrs . '>' . $code . '</script>';
    }
}
