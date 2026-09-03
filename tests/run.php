<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'MonaPay\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    require $path;
});

use MonaPay\Client;
use MonaPay\Webhook;

$assertions = 0;

function check(bool $condition, string $message): void
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: $message\n");
        exit(1);
    }
}

$timestamp = time();
$rawBody = json_encode(['amount' => 2500000, 'transaction_code' => 'FT1']);
$secret = 'test-secret';
$signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
$valid = Webhook::verify($rawBody, [
    'X-Mona-Timestamp' => (string) $timestamp,
    'x-mona-signature' => $signature,
], $secret);
check($valid['ok'] === true, 'Webhook hợp lệ phải được chấp nhận');
check($valid['payload']['transaction_code'] === 'FT1', 'Webhook phải parse payload');

$invalid = Webhook::verify($rawBody, [
    'x-mona-timestamp' => (string) $timestamp,
    'x-mona-signature' => 'sha256=' . str_repeat('0', 64),
], $secret);
check($invalid['reason'] === 'invalid_signature', 'Phải từ chối chữ ký sai');

$expiredTimestamp = $timestamp - 301;
$expired = Webhook::verify($rawBody, [
    'x-mona-timestamp' => (string) $expiredTimestamp,
    'x-mona-signature' => 'sha256=' . hash_hmac('sha256', $expiredTimestamp . '.' . $rawBody, $secret),
], $secret, 300);
check($expired['reason'] === 'timestamp_out_of_tolerance', 'Phải từ chối timestamp cũ');

$requests = [];
$transport = static function (array $request) use (&$requests): array {
    $requests[] = $request;
    if (substr($request['url'], -19) === '/api/v1/oauth/token') {
        $body = json_decode((string) $request['body'], true);
        check($body === ['grant_type' => 'client_credentials', 'client_id' => 'client-id', 'client_secret' => 'client-secret'], 'OAuth body phải đúng');
        return ['status' => 200, 'body' => ['success' => true, 'data' => ['access_token' => 'token-1', 'expires_in' => 3600]]];
    }
    return ['status' => 200, 'body' => ['success' => true, 'data' => ['id' => 'hook-1']]];
};
$client = Client::fromEnv(['MONAPAY_CLIENT_ID' => 'client-id', 'MONAPAY_CLIENT_SECRET' => 'client-secret', 'MONAPAY_BASE_URL' => 'https://example.test/'], $transport);
$client->webhooks->create(['name' => 'Shop', 'webhook_url' => 'https://shop.test/hook']);
$client->me();

check(count($requests) === 3, 'Token phải được cache sau một lần login');
check($requests[1]['url'] === 'https://example.test/api/v1/client-webhooks', 'URL phải được dựng đúng');
check($requests[1]['headers']['Authorization'] === 'Bearer token-1', 'Phải gửi Bearer token');
check($requests[1]['headers']['X-Client-Secret'] === 'client-secret', 'POST phải gửi client secret');
check(!isset($requests[2]['headers']['X-Client-Secret']), 'GET không gửi client secret');

fwrite(STDOUT, "OK: $assertions assertions\n");
