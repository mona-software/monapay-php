<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class BankAccounts
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function list() { return $this->client->request('GET', '/api/v1/client/bank-accounts'); }
}
