<?php

use MonaPay\Webhook;

add_action('rest_api_init', static function (): void {
    register_rest_route('monapay/v1', '/webhook', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => static function (WP_REST_Request $request) {
            $result = Webhook::verify(
                $request->get_body(),
                $request->get_headers(),
                MONA_WEBHOOK_SECRET
            );
            if (!$result['ok']) {
                return new WP_REST_Response(['ok' => false, 'reason' => $result['reason']], 401);
            }
            // Lưu transaction_code vào cột UNIQUE trước khi cập nhật đơn.
            mona_save_once($result['payload']['transaction_code'], $result['payload']);
            return new WP_REST_Response(['ok' => true], 200);
        },
    ]);
});
