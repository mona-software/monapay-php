<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class EmailConfigs
{
    private Client $client;
    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function list() { return $this->client->request('GET', '/api/v1/email-configs'); }
    /** @param array<string,mixed> $body @return mixed */
    public function create(array $body) { return $this->client->request('POST', '/api/v1/email-configs', $body); }
    /** @return mixed */
    public function get(string $id) { return $this->client->request('GET', '/api/v1/email-configs/' . rawurlencode($id)); }
    /** @param array<string,mixed> $body @return mixed */
    public function update(string $id, array $body) { return $this->client->request('PUT', '/api/v1/email-configs/' . rawurlencode($id), $body); }
    /** @return mixed */
    public function remove(string $id) { return $this->client->request('DELETE', '/api/v1/email-configs/' . rawurlencode($id)); }
    /** @return mixed */
    public function verify(string $id, string $email, string $code) { return $this->client->request('POST', '/api/v1/email-configs/' . rawurlencode($id) . '/verify', ['email' => $email, 'code' => $code]); }
    /** @return mixed */
    public function resendVerification(string $id, string $email) { return $this->client->request('POST', '/api/v1/email-configs/' . rawurlencode($id) . '/resend-verification', ['email' => $email]); }
    /** @return mixed */
    public function test(string $id) { return $this->client->request('POST', '/api/v1/email-configs/' . rawurlencode($id) . '/test', []); }
}
