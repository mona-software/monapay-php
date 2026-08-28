<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class QrPayments
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $body @return mixed */
    public function generate(array $body)
    {
        return $this->client->request('POST', '/api/v1/acb/qr-payment/generate', $body);
    }

    /** @param array<string,mixed>|null $body @return mixed */
    public function cancel(string $id, ?array $body = null)
    {
        return $this->client->request(
            'DELETE', '/api/v1/acb/qr-payment/' . rawurlencode($id) . '/cancellation', $body
        );
    }
}
