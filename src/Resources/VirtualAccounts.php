<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class VirtualAccounts
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $body @return mixed */
    public function register(array $body)
    {
        return $this->client->request('POST', '/api/v1/acb/virtual-account/registration', $body);
    }

    /** @return mixed */
    public function verify(string $requestId, string $code)
    {
        return $this->client->request(
            'POST', '/api/v1/acb/' . rawurlencode($requestId) . '/virtual-account/verification', ['code' => $code]
        );
    }

    /** @param array<string,mixed> $body @return mixed */
    public function registerNotification(string $vaId, array $body)
    {
        return $this->client->request(
            'POST', '/api/v1/acb/' . rawurlencode($vaId) . '/notification/registration', $body
        );
    }

    /** @return mixed */
    public function verifyNotification(string $requestId, string $code)
    {
        return $this->client->request(
            'POST', '/api/v1/acb/' . rawurlencode($requestId) . '/notification/verification', ['code' => $code]
        );
    }

    /** @return mixed */
    public function list(string $bankAccountId)
    {
        return $this->client->request(
            'GET', '/api/v1/acb/' . rawurlencode($bankAccountId) . '/virtual-account/retrieve'
        );
    }
}
