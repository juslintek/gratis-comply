=== Gratis Comply ===
Contributors: juslintek
Tags: gdpr, cookie consent, privacy, ccpa, compliance
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

GDPR/CCPA compliance: cookie consent banner, privacy policy generator, and data requests.

== Description ==

GDPR/CCPA compliance: cookie consent banner, privacy policy generator, and data requests.

Part of the Gratis suite — a collection of free, no-upsell WordPress plugins.

**Features:**

* Accessible cookie consent banner with granular category toggles (Necessary, Analytics, Marketing)
* Google Consent Mode v2 — emits `gtag` consent signals (denied by default, updated on accept)
* Script gating: tag third-party scripts so they only run after consent
* One-click privacy policy page generator
* Consent logging via a daily-rotating salted visitor hash
* Data export and erasure request handling

**Philosophy:**

Everything is free. No "Pro" version, no locked features, no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure it from its settings screen in the WordPress admin.

== Frequently Asked Questions ==

= Is this plugin really free? =

Yes. Everything is free — no "Pro" version, no locked features, and no nag screens. Optional managed cloud services may be offered separately to cover infrastructure costs.

= Does this plugin make external HTTP requests? =

No. This plugin runs entirely locally and does not phone home or send analytics.

= Where do I get support? =

Open an issue on the plugin's GitHub repository.

= How do I stop a script from running until the visitor consents? =

Use the `gratis_comply_consent_script()` template tag (or `\Gratis\Comply\Consent::script_tag()`).
It outputs an inert `<script type="text/plain" data-gratis-consent="...">` placeholder that the
front-end controller activates only after the visitor accepts that category.

Inline script:

`echo gratis_comply_consent_script( 'analytics', 'gtag("event","page_view");' );`

External script (the `src` is deferred as `data-src` until consent):

`echo gratis_comply_consent_script( 'marketing', '', array( 'src' => 'https://example.com/pixel.js' ) );`

This renders, before consent:

`<script type="text/plain" data-gratis-consent="analytics">gtag("event","page_view");</script>`

On accept it is rewritten into a live, executing `<script>`. The category slug matches the banner
toggles (`analytics`, `marketing`, or `necessary` which always runs).

= Can I customise categories, the Consent Mode mapping, or reopen the banner? =

Yes:

* `gratis_comply_consent_categories` — filter the category list (label, description, required flag).
* `gratis_comply_consent_mode_map` — filter which Consent Mode v2 signals each category grants.
* Give any link or button a `data-gratis-consent-open` attribute (or call `window.gratisComply.show()`)
  to reopen the preferences banner.
* A `gratis-comply:consent` DOM event fires on `document` after every choice, with the granular
  state in `event.detail`.

== Third-Party Services ==

This plugin integrates with Google's Consent Mode v2 API by emitting `gtag('consent', ...)` signals on the frontend. No data is sent to Google by this plugin directly — the signals are consumed by Google Tag Manager or gtag.js if separately installed on your site.

= Google Consent Mode v2 =

The plugin outputs JavaScript consent default/update calls that Google's tag infrastructure reads.
* [Google Privacy Policy](https://policies.google.com/privacy)
* [Consent Mode documentation](https://developers.google.com/tag-platform/security/guides/consent)

The cookie consent banner itself is fully self-contained and does not load any external resources. All consent data is stored locally in a first-party cookie.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Cookie consent banner with granular category toggles (Necessary, Analytics, Marketing).
* Google Consent Mode v2: `gtag` consent default (denied) emitted early in the head, updated on accept.
* Script gating helper (`gratis_comply_consent_script()`) for consent-aware third-party scripts.
* Granular consent stored in a first-party cookie and the daily-rotating salted consent log.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
