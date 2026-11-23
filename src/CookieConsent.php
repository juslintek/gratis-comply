<?php declare(strict_types=1);

namespace Gratis\Comply;

defined('ABSPATH') || exit;

/**
 * Front-end cookie consent: Google Consent Mode v2 + granular category banner.
 *
 * `render_head()` runs as early as possible in <head> to emit the Consent Mode
 * v2 default (everything denied except security_storage) before any tag fires,
 * then a controller that: re-applies a returning visitor's stored choice (cookie),
 * unblocks consent-gated <script> tags, and fires the Consent Mode `update`.
 *
 * `render()` prints the banner (Necessary/Analytics/Marketing toggles) in the
 * footer. The granular choice is stored in a first-party cookie and POSTed to
 * the REST collector for the audit log.
 */
final class CookieConsent {

    /** Cookie that stores the visitor's granular choice (client-side). */
    private const COOKIE = 'gratis_consent';

    /** Consent Mode `wait_for_update` window (ms) — lets early tags wait for the update. */
    private const WAIT_FOR_UPDATE = 500;

    /**
     * Emit Consent Mode v2 default + the consent controller, as early as
     * possible in <head> (hooked at wp_head priority 0).
     */
    public static function render_head(): void {
        if (is_admin()) {
            return;
        }

        $consent_mode = Settings::consentModeEnabled();
        if (! $consent_mode && ! Settings::bannerEnabled()) {
            return;
        }

        $config = [
            'rest'         => esc_url_raw(rest_url('gratis-comply/v1/consent')),
            'cookie'       => self::COOKIE,
            'days'         => 365,
            'consentMode'  => $consent_mode,
            'map'          => Consent::consentModeMap(),
            'nonEssential' => Consent::nonEssentialSlugs(),
        ];
        $default_consent = array_merge(
            Consent::defaultSignals(),
            ['wait_for_update' => self::WAIT_FOR_UPDATE]
        );

        // Config block — only the wp_json_encode() values are dynamic (sniff-safe).
        echo "\n" . '<script>window.gratisComplyCfg=' . wp_json_encode($config)
            . ';window.gratisComplyConsentDefault=' . wp_json_encode($default_consent)
            . ';</script>' . "\n";

        // Controller — a single static string literal (no interpolation), using
        // window.gratisComplyCfg / window.gratisComplyConsentDefault above.
        echo '<script>'
            . '(function(w,d){'
            . 'var CFG=w.gratisComplyCfg||{};'
            . 'var DEF=w.gratisComplyConsentDefault||{};'
            . 'var API=w.gratisComply=w.gratisComply||{};'
            . 'function gtag(){(w.dataLayer=w.dataLayer||[]).push(arguments);}'
            . 'if(CFG.consentMode){w.gtag=w.gtag||gtag;gtag("consent","default",DEF);}'
            . 'function readCookie(n){var a=d.cookie?d.cookie.split(";"):[];for(var i=0;i<a.length;i++){var p=a[i];while(p.charAt(0)===" "){p=p.substring(1);}if(p.indexOf(n+"=")===0){return p.substring(n.length+1);}}return "";}'
            . 'function readConsent(){var r=readCookie(CFG.cookie);if(!r){return null;}try{return JSON.parse(decodeURIComponent(r));}catch(e){return null;}}'
            . 'function signalsFor(s){var sig={},m=CFG.map||{};for(var c in m){if(!m.hasOwnProperty(c)){continue;}var v=(s&&s[c])?"granted":"denied";for(var i=0;i<m[c].length;i++){sig[m[c][i]]=v;}}return sig;}'
            . 'function activate(s){var n=d.querySelectorAll("script[data-gratis-consent]");for(var i=0;i<n.length;i++){var node=n[i];var c=node.getAttribute("data-gratis-consent");if(c!=="necessary"&&!(s&&s[c])){continue;}var el=d.createElement("script");for(var j=0;j<node.attributes.length;j++){var at=node.attributes[j];if(at.name==="type"||at.name==="data-gratis-consent"){continue;}if(at.name==="data-src"){el.setAttribute("src",at.value);continue;}el.setAttribute(at.name,at.value);}if(!node.getAttribute("data-src")){el.text=node.textContent;}node.parentNode.replaceChild(el,node);}}'
            . 'function emit(s){try{d.dispatchEvent(new CustomEvent("gratis-comply:consent",{detail:s}));}catch(e){}}'
            . 'function persist(s){var v=encodeURIComponent(JSON.stringify(s));var sec=(w.location.protocol==="https:")?";secure":"";d.cookie=CFG.cookie+"="+v+";path=/;max-age="+(CFG.days*86400)+";samesite=lax"+sec;}'
            . 'function apply(s,store){s=s||{};s.necessary=true;if(store){persist(s);}if(CFG.consentMode){gtag("consent","update",signalsFor(s));}activate(s);emit(s);}'
            . 'API.readConsent=readConsent;API.signalsFor=signalsFor;API.activate=activate;API.apply=function(s){apply(s,true);};'
            . 'var stored=readConsent();'
            . 'if(stored&&CFG.consentMode){gtag("consent","update",signalsFor(stored));}'
            . 'function ne(){return CFG.nonEssential||[];}'
            . 'function sync(b,s){if(!b){return;}var a=ne();for(var i=0;i<a.length;i++){var cb=b.querySelector("input[data-gratis-consent-cat="+a[i]+"]");if(cb){cb.checked=!!(s&&s[a[i]]);}}}'
            . 'function show(b){if(b){sync(b,readConsent());b.style.display="block";}}'
            . 'function setup(){'
            . 'if(stored){activate(stored);}'
            . 'var b=d.getElementById("gratis-consent");'
            . 'var op=d.querySelectorAll("[data-gratis-consent-open]");'
            . 'API.show=function(){show(b);};'
            . 'for(var i=0;i<op.length;i++){op[i].addEventListener("click",function(e){e.preventDefault();show(b);});}'
            . 'if(!b){return;}'
            . 'var lsAccepted=false;try{lsAccepted=!!w.localStorage.getItem("gratis_cookie_accepted");}catch(e){}'
            . 'if(!stored&&!lsAccepted){sync(b,null);b.style.display="block";}'
            . 'function collect(){var s={},a=ne();for(var i=0;i<a.length;i++){var cb=b.querySelector("input[data-gratis-consent-cat="+a[i]+"]");s[a[i]]=!!(cb&&cb.checked);}return s;}'
            . 'function all(v){var s={},a=ne();for(var i=0;i<a.length;i++){s[a[i]]=v;}return s;}'
            . 'function save(s){apply(s,true);b.style.display="none";try{w.localStorage.setItem("gratis_cookie_accepted","1");}catch(e){}if(w.fetch){w.fetch(CFG.rest,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({categories:s})}).catch(function(){});}}'
            . 'b.addEventListener("click",function(e){var t=e.target;if(!t||!t.getAttribute){return;}var act=t.getAttribute("data-gratis-consent-action");if(!act){return;}if(act==="accept"){save(all(true));}else if(act==="reject"){save(all(false));}else if(act==="save"){save(collect());}});'
            . '}'
            . 'if(d.readyState!=="loading"){setup();}else{d.addEventListener("DOMContentLoaded",setup);}'
            . '})(window,document);'
            . '</script>' . "\n";
    }

    /** Print the consent banner (category toggles) in the footer. */
    public static function render(): void {
        if (is_admin() || ! Settings::bannerEnabled()) {
            return;
        }

        $text        = Settings::bannerText();
        $privacy_url = Settings::privacyUrl();

        echo '<div id="gratis-consent" class="gratis-consent" role="dialog" aria-modal="false" aria-label="' . esc_attr__('Cookie consent', 'gratis-comply') . '" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a1e2e;border-top:1px solid rgba(255,255,255,0.1);padding:16px 24px;z-index:99999;color:#fff;font-size:14px">';
        echo '<div style="max-width:1200px;margin:0 auto">';

        echo '<p style="margin:0 0 12px;color:rgba(255,255,255,0.7)">' . esc_html($text);
        if ($privacy_url !== '') {
            echo ' <a href="' . esc_url($privacy_url) . '" style="color:#22C55E">' . esc_html__('Privacy Policy', 'gratis-comply') . '</a>';
        }
        echo '</p>';

        echo '<fieldset style="border:0;margin:0 0 12px;padding:0;display:flex;gap:18px;flex-wrap:wrap">';
        echo '<legend style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">' . esc_html__('Choose which cookies to allow', 'gratis-comply') . '</legend>';
        foreach (Consent::categories() as $slug => $cat) {
            $slug     = sanitize_key((string) $slug);
            $required = ! empty($cat['required']);
            $label    = isset($cat['label']) ? (string) $cat['label'] : $slug;
            $desc     = isset($cat['desc']) ? (string) $cat['desc'] : '';

            echo '<label style="display:flex;align-items:center;gap:6px;color:rgba(255,255,255,0.85)" title="' . esc_attr($desc) . '">';
            if ($required) {
                echo '<input type="checkbox" checked disabled aria-label="' . esc_attr($label) . '">';
            } else {
                echo '<input type="checkbox" data-gratis-consent-cat="' . esc_attr($slug) . '">';
            }
            echo '<span>' . esc_html($label) . '</span>';
            echo '</label>';
        }
        echo '</fieldset>';

        echo '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">';
        echo '<button type="button" data-gratis-consent-action="accept" style="background:#22C55E;color:#0F172A;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">' . esc_html__('Accept all', 'gratis-comply') . '</button>';
        echo '<button type="button" data-gratis-consent-action="save" style="background:none;border:1px solid rgba(255,255,255,0.25);color:#fff;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">' . esc_html__('Save preferences', 'gratis-comply') . '</button>';
        echo '<button type="button" data-gratis-consent-action="reject" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.6);padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">' . esc_html__('Reject all', 'gratis-comply') . '</button>';
        echo '</div>';

        echo '</div></div>' . "\n";
    }
}
