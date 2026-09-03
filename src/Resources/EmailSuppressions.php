<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class EmailSuppressions
{
    private Client $client;
    public function __construct(Client $client) { $this->client = $client; }
    /** @return mixed */
    public function list() { return $this->client->request('GET', '/api/v1/email-suppressions'); }
    /** @return mixed */
    public function remove(string $email) { return $this->client->request('DELETE', '/api/v1/email-suppressions/' . rawurlencode($email)); }
}
