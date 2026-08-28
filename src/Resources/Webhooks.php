<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class Webhooks
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function list() { return $this->client->request('GET', '/api/v1/client-webhooks'); }

    /** @param array<string,mixed> $body @return mixed */
    public function create(array $body) { return $this->client->request('POST', '/api/v1/client-webhooks', $body); }

    /** @param array<string,mixed> $body @return mixed */
    public function update(string $id, array $body)
    {
        return $this->client->request('PUT', '/api/v1/client-webhooks/' . rawurlencode($id), $body);
    }

    /** @return mixed */
    public function remove(string $id)
    {
        return $this->client->request('DELETE', '/api/v1/client-webhooks/' . rawurlencode($id));
    }

    /** @param array<string,mixed> $body @return mixed */
    public function test(array $body)
    {
        return $this->client->request('POST', '/api/v1/client-webhooks/test', $body);
    }
}
