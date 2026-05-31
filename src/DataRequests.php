<?php declare(strict_types=1);
namespace Gratis\Comply;

final class DataRequests
{
    public static function register(): void
    {
        register_rest_route('gratis-comply/v1', '/consent', [
            'methods' => 'POST', 'permission_callback' => '__return_true',
            'callback' => function ($req) {
                $choice = $req->get_param('choice');
                // Log consent for audit trail
                $log = get_option('gratis_consent_log', []);
                $log[] = ['choice' => $choice, 'ip_hash' => substr(md5($_SERVER['REMOTE_ADDR'] ?? ''), 0, 8), 'time' => gmdate('c')];
                if (count($log) > 10000) $log = array_slice($log, -10000);
                update_option('gratis_consent_log', $log, false);
                return new \WP_REST_Response(['recorded' => true]);
            },
        ]);

        register_rest_route('gratis-comply/v1', '/data-request', [
            'methods' => 'POST', 'permission_callback' => '__return_true',
            'callback' => function ($req) {
                $email = sanitize_email($req->get_param('email'));
                $type = $req->get_param('type'); // 'export' or 'delete'
                if (!$email || !in_array($type, ['export', 'delete'])) {
                    return new \WP_REST_Response(['error' => 'Invalid request'], 400);
                }
                // Create a WP privacy request
                $request_id = wp_create_user_request($email, $type === 'delete' ? 'remove_personal_data' : 'export_personal_data');
                return new \WP_REST_Response(['success' => !is_wp_error($request_id), 'request_id' => $request_id]);
            },
        ]);
    }
}
