<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MonaPay\Webhook;

final class LaravelWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $result = Webhook::verify(
            $request->getContent(),
            $request->headers->all(),
            (string) config('services.monapay.webhook_secret')
        );
        if (!$result['ok']) {
            return response()->json(['ok' => false, 'reason' => $result['reason']], 401);
        }

        // updateOrCreate theo transaction_code để webhook gửi lại không xử lý trùng.
        MonaTransaction::updateOrCreate(
            ['transaction_code' => $result['payload']['transaction_code']],
            $result['payload']
        );
        return response()->json(['ok' => true], 200);
    }
}
