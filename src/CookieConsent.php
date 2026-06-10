<?php declare(strict_types=1);
namespace Gratis\Comply;

final class CookieConsent
{
    public static function render(): void
    {
        if (is_admin()) return;
        echo '<div id="gratis-consent" style="display:none;position:fixed;bottom:0;left:0;right:0;background:#1a1e2e;border-top:1px solid rgba(255,255,255,0.1);padding:16px 24px;z-index:99999;color:#fff;font-size:14px">';
        echo '<div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">';
        echo '<p style="margin:0;color:rgba(255,255,255,0.7)">We use essential cookies only. No tracking. <a href="/privacy-policy/" style="color:#22C55E">Privacy Policy</a></p>';
        echo '<div><button onclick="gratisConsent(\'accept\')" style="background:#22C55E;color:#0F172A;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">Accept</button> ';
        echo '<button onclick="gratisConsent(\'reject\')" style="background:none;border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.6);padding:8px 20px;border-radius:6px;cursor:pointer;font-size:13px">Reject</button></div>';
        echo '</div></div>';
        echo '<script>!function(){if(!localStorage.getItem("gratis_consent")){document.getElementById("gratis-consent").style.display="block"}}();function gratisConsent(c){localStorage.setItem("gratis_consent",c);document.getElementById("gratis-consent").style.display="none";fetch("' . rest_url('gratis-comply/v1/consent') . '",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({choice:c})})}</script>' . "\n";
    }
}
