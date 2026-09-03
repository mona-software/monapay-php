# monapay/php-sdk

MONA Pay là cổng thanh toán và API ngân hàng của The MONA Group, giúp doanh nghiệp Việt Nam nhận và xác nhận tiền chuyển khoản theo thời gian thực qua tài khoản ảo (VA), VietQR, webhook và Telegram, thiết kế để cả lập trình viên lẫn AI agent tích hợp trong vài phút.

SDK PHP 7.4+, PSR-4, dùng `curl` và không phụ thuộc package bên thứ ba. MONA Pay miễn phí hoàn toàn.

## Xác thực cho AI agent

```bash
export MONAPAY_CLIENT_ID="client-id"
export MONAPAY_CLIENT_SECRET="client-secret"
export MONAPAY_BASE_URL="https://api.monapay.vn"
```

```php
$mona = Client::fromEnv();
$profile = $mona->me();
$qr = $mona->qr->generate($qrBody);
$sandbox = $mona->sandbox->createTransaction(['virtual_account_number' => 'MONA123', 'amount' => 10000, 'description' => 'AI test']);
var_dump($profile);
```

`Client::fromEnv()` ưu tiên client credentials, cache token tới gần hạn và tự lấy lại khi gặp HTTP 401. Username/password chỉ là fallback tương thích cũ, không dùng cho AI agent vì sẽ gãy khi bật 2FA.

## Cài đặt

```bash
composer require monapay/php-sdk
```

## Bắt đầu nhanh

```php
<?php

use MonaPay\Client;

$mona = new Client(
    getenv('MONA_USERNAME'),
    getenv('MONA_PASSWORD'),
    getenv('MONA_CLIENT_SECRET') ?: null
);

// Tự login và cache token.
var_dump($mona->me());

// Lần đầu: lưu client_secret vì API chỉ hiện đúng một lần.
$key = $mona->keys->generate('Web ban hang');
echo $key['client_secret'];

$mona->webhooks->create([
    'name' => 'Web ban hang',
    'webhook_url' => 'https://shop.vn/webhooks/monapay',
    'auth_type' => 'HMAC_SHA256',
    'secret_key' => getenv('MONA_WEBHOOK_SECRET'),
    'payload_format' => 'application/json',
]);

$qr = $mona->qr->generate([
    'ownerNumber' => '123456789', 'ownerType' => 'ORG',
    'merchantId' => 'MC00012345', 'terminalId' => 'TM0001', 'orderId' => 'DH10234',
    'virtualAccountPrefix' => 'MONA', 'beneficiaryName' => 'CONG TY ABC',
    'amount' => 2500000, 'description' => 'Thanh toan DH10234',
]);
echo $qr['qr_data_url'];
```

Client tự login lại và thử request đúng một lần khi gặp HTTP 401. Các method trả trực tiếp `data`; `ApiException` có `status` và `body`.

Các resource: `$keys`, `$paymentProfile`, `$checkouts`, `$va`, `$bankAccounts`, `$qr`, `$transactions`, `$webhooks`, `$webhookLogs`, `$sandbox`, `$emailConfigs`, `$emailLogs`, `$emailSuppressions`. Ví dụ đọc phân trang:

```php
foreach ($mona->iterTransactions('MONA0000010234', 1, 100) as $tx) {
    echo $tx['transaction_code'] . ': ' . $tx['amount'];
}

$mona->transactions->retry($transactionId, 'WEBHOOK', $webhookConfigId);
```

## Trang thanh toán (hosted checkout)

```php
$checkout = $mona->checkouts->create(['amount' => 250000, 'order_code' => 'DH10234', 'return_url' => 'https://shop.vn/payment/return']);
header('Location: ' . $checkout['checkout_url']);
if ($event['type'] === 'CHECKOUT_PAID') {
    fulfillOnce($event['data']['order_code']);
}
```

SDK tự sinh `Idempotency-Key` cho `create` và `cancel`; truyền đối số thứ hai khi anh chị cần dùng key riêng. Nguồn sự thật để giao hàng là webhook `CHECKOUT_PAID` hoặc kết quả `get`, không phải redirect trình duyệt.

## Xác thực webhook

```php
use MonaPay\Webhook;

$rawBody = file_get_contents('php://input');
$result = Webhook::verify($rawBody, getallheaders(), getenv('MONA_WEBHOOK_SECRET'));
if (!$result['ok']) {
    http_response_code(401);
    exit($result['reason']);
}
saveOnce($result['payload']['transaction_code'], $result['payload']);
http_response_code(200);
```

Luôn kiểm trên raw body và dùng `transaction_code` làm unique key. Ví dụ WordPress REST và Laravel nằm trong `examples/`.

Tài liệu: https://monapay.vn/docs · AI/LLM: https://monapay.vn/llms.txt · Hotline 1900 636 648 · info@themona.global

## Test

```bash
php tests/run.php
```

License MIT.
