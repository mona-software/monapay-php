<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class Checkouts
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $body @return mixed */
    public function create(array $body, ?string $idempotencyKey = null)
    {
        return $this->client->request('POST', '/api/v1/checkouts', $body, [], [
            'Idempotency-Key' => $idempotencyKey ?: self::uuid(),
        ]);
    }

    /** @return mixed */
    public function get(string $id)
    {
        return $this->client->request('GET', '/api/v1/checkouts/' . rawurlencode($id));
    }

    /** @param array{status?:string,order_code?:string,from_date?:string,to_date?:string,page?:int,limit?:int} $filter @return mixed */
    public function list(array $filter = [])
    {
        return $this->client->request('GET', '/api/v1/checkouts', null, $filter);
    }

    /** @return mixed */
    public function cancel(string $id, ?string $idempotencyKey = null)
    {
        return $this->client->request('POST', '/api/v1/checkouts/' . rawurlencode($id) . '/cancel', [], [], [
            'Idempotency-Key' => $idempotencyKey ?: self::uuid(),
        ]);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
