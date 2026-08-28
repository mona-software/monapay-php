<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use InvalidArgumentException;
use MonaPay\Client;

final class Transactions
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @return mixed */
    public function list(string $virtualAccountNumber, int $page = 1, int $limit = 100)
    {
        if ($virtualAccountNumber === '') {
            throw new InvalidArgumentException('virtualAccountNumber là bắt buộc');
        }
        return $this->client->request(
            'GET',
            '/api/v1/acb/virtual-account/transactions',
            null,
            ['virtual_account_number' => $virtualAccountNumber, 'page' => $page, 'limit' => $limit]
        );
    }

    /** @return \Generator<int,mixed> */
    public function iterate(string $virtualAccountNumber, int $page = 1, int $limit = 100): \Generator
    {
        $currentPage = $page;
        while (true) {
            $result = $this->list($virtualAccountNumber, $currentPage, $limit);
            foreach (($result['data'] ?? []) as $transaction) {
                yield $transaction;
            }
            $hasNext = array_key_exists('has_next', $result)
                ? (bool) $result['has_next']
                : $currentPage < (int) ($result['last_page'] ?? $currentPage);
            if (!$hasNext) {
                return;
            }
            $currentPage++;
        }
    }

    /** @return mixed */
    public function retry(string $transactionId, string $targetType, ?string $targetId = null)
    {
        $body = ['target_type' => $targetType];
        if ($targetId !== null) {
            $body['target_id'] = $targetId;
        }
        return $this->client->request(
            'POST',
            '/api/v1/acb/virtual-account/transactions/' . rawurlencode($transactionId) . '/retry',
            $body
        );
    }
}
