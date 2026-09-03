<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class PaymentProfile
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function get() { return $this->client->request('GET', '/api/v1/payment-profile'); }

    /** @param array<string,mixed> $body @return mixed */
    public function set(array $body) { return $this->client->request('PUT', '/api/v1/payment-profile', $body); }

    /** @return mixed */
    public function rotateReturnSecret()
    {
        return $this->client->request('POST', '/api/v1/payment-profile/rotate-return-secret', []);
    }

    /** @param array{password?:string,totp_code?:string} $confirmation @return mixed */
    public function revealReturnSecret(array $confirmation)
    {
        return $this->client->request('POST', '/api/v1/payment-profile/reveal-return-secret', $confirmation);
    }
}
