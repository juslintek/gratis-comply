<?php declare(strict_types=1);

namespace Gratis\Comply;

defined('ABSPATH') || exit;

/**
 * Front-end cookie consent banner.
 *
 * Renders a settings-aware, accessible consent banner in the footer. Consent
 * state is stored client-side in localStorage (so server markup stays
 * cache-friendly) and the choice is POSTed to the REST collector for an audit
 * trail.
 */
final class CookieConsent {

    /** Print the consent banner + controller script in the footer. */
    public static function render(): void {
        if (is_admin() || ! Settings::bannerEnabled()) {
            return;
        }

        $text        = Settings::bannerText();
        $privacy_url  = Settings::privacyUrl();
        $consent_rest = esc_url_raw(rest_url('gratis-comply/v1/consent'));

        echo '<div id="gratis-consent" class="gratis-consent" role="region" aria-label="' . esc_attr__('Cookie consent', 'gratis-comply') . '" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a1e2e;border-top:1px solid rgba(255,255,255,0.1);padding:16px 24px;z-index:99999;color:#fff;font-size:14px">';
        echo '<div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">';
        echo '<p style="margin:0;color:rgba(255,255,255,0.7)">' . esc_html($text);
        if ($privacy_url !== '') {
            echo ' <a href="' . esc_url($privacy_url) . '" style="color:#22C55E">' . esc_html__('Privacy Policy', 'gratis-comply') . '</a>';
        }
        echo '</p>';
        echo '<div><button type="button" data-gratis-consent="accept" style="background:#22C55E;color:#0F172A;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">' . esc_html__('Accept', 'gratis-comply') . '</button> ';
        echo '<button type="button" data-gratis-consent="reject" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.6);padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">' . esc_html__('Reject', 'gratis-comply') . '</button></div>';
        echo '</div></div>';

        echo '<script>(function(){var b=document.getElementById("gratis-consent");if(!b)return;if(!localStorage.getItem("gratis_consent")){b.style.display="block";}function choose(c){localStorage.setItem("gratis_consent",c);b.style.display="none";fetch(' . wp_json_encode($consent_rest) . ',{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({choice:c})});}b.querySelectorAll("[data-gratis-consent]").forEach(function(el){el.addEventListener("click",function(){choose(el.getAttribute("data-gratis-consent"));});});})();</script>' . "\n";
    }
}
