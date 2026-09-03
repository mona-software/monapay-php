<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class Keys
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function generate(string $name = 'Default Key')
    {
        $data = $this->client->request('POST', '/api/v1/client-keys/generate', ['name' => $name]);
        if (is_array($data) && !empty($data['client_secret'])) {
            $this->client->setClientSecret((string) $data['client_secret']);
        }
        return $data;
    }

    /** @return mixed */
    public function list() { return $this->client->request('GET', '/api/v1/client-keys/list'); }

    /** @return mixed */
    public function destroy(string $id)
    {
        return $this->client->request('DELETE', '/api/v1/client-keys/destroy/' . rawurlencode($id));
    }

    /** @param array{password?:string,totp_code?:string} $confirmation @return mixed */
    public function reveal(string $id, array $confirmation)
    {
        return $this->client->request('POST', '/api/v1/client-keys/' . rawurlencode($id) . '/reveal', $confirmation);
    }

    /** @return mixed */
    public function rotate(string $id)
    {
        $data = $this->client->request('POST', '/api/v1/client-keys/' . rawurlencode($id) . '/rotate', []);
        if (is_array($data) && !empty($data['client_secret'])) {
            $this->client->setClientSecret((string) $data['client_secret']);
        }
        return $data;
    }
}
