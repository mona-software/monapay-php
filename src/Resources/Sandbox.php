<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class Sandbox
{
    private Client $client;
    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $body @return mixed */
    public function createTransaction(array $body)
    {
        return $this->client->request('POST', '/api/v1/sandbox/transactions', $body);
    }
}
