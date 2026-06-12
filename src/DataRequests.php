<?php declare(strict_types=1);

namespace Gratis\Comply;

defined('ABSPATH') || exit;

/**
 * REST endpoints for consent logging and GDPR data requests.
 *
 * Both routes are public collectors by design (no nonce — WPCS
 * NonceVerification targets $_POST/$_GET, not REST params). The consent log
 * stores only a daily-rotating salted hash of the visitor, never the raw IP.
 */
final class DataRequests {

    private const OPT_LOG     = 'gratis_consent_log';
    private const MAX_ENTRIES = 10000;

    /** Register both REST routes. */
    public static function register(): void {
        register_rest_route('gratis-comply/v1', '/consent', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'recordConsent'],
        ]);

        register_rest_route('gratis-comply/v1', '/data-request', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [__CLASS__, 'handleDataRequest'],
        ]);
    }

    /** Log a consent choice to the audit trail. */
    public static function recordConsent(\WP_REST_Request $req): \WP_REST_Response {
        $choice = sanitize_key((string) $req->get_param('choice'));
        if (! in_array($choice, ['accept', 'reject'], true)) {
            return new \WP_REST_Response(['error' => 'Invalid choice'], 400);
        }

        $log   = get_option(self::OPT_LOG, []);
        $log   = is_array($log) ? $log : [];
        $log[] = [
            'choice'  => $choice,
            'visitor' => self::visitorHash(),
            'time'    => gmdate('c'),
        ];
        if (count($log) > self::MAX_ENTRIES) {
            $log = array_slice($log, -self::MAX_ENTRIES);
        }
        update_option(self::OPT_LOG, $log, false);

        return new \WP_REST_Response(['recorded' => true]);
    }

    /** Create a WordPress privacy export/erasure request. */
    public static function handleDataRequest(\WP_REST_Request $req): \WP_REST_Response {
        $email = sanitize_email((string) $req->get_param('email'));
        $type  = sanitize_key((string) $req->get_param('type'));

        if ($email === '' || ! in_array($type, ['export', 'delete'], true)) {
            return new \WP_REST_Response(['error' => 'Invalid request'], 400);
        }

        $action     = $type === 'delete' ? 'remove_personal_data' : 'export_personal_data';
        $request_id = wp_create_user_request($email, $action);

        if (is_wp_error($request_id)) {
            return new \WP_REST_Response(['error' => $request_id->get_error_message()], 400);
        }

        return new \WP_REST_Response(['success' => true, 'request_id' => $request_id]);
    }

    /**
     * Daily-rotating salted hash of the visitor — never stores the raw IP.
     *
     * The date component means the same visitor can't be correlated across
     * days, and wp_salt() (a wp-config secret) prevents brute-forcing back.
     */
    private static function visitorHash(): string {
        $ip = isset($_SERVER['REMOTE_ADDR'])
            ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            : '';
        $ip = (string) filter_var($ip, FILTER_VALIDATE_IP);

        return substr(hash('sha256', wp_salt('nonce') . '|' . gmdate('Y-m-d') . '|' . $ip), 0, 16);
    }
}
