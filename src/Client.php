<?php

declare(strict_types=1);

namespace MonaPay;

use MonaPay\Resources\BankAccounts;
use MonaPay\Resources\Keys;
use MonaPay\Resources\QrPayments;
use MonaPay\Resources\Transactions;
use MonaPay\Resources\VirtualAccounts;
use MonaPay\Resources\WebhookLogs;
use MonaPay\Resources\Webhooks;

final class Client
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $clientSecret;
    private ?string $accessToken = null;
    private int $timeout;

    /** @var callable|null */
    private $transport;

    public Keys $keys;
    public VirtualAccounts $va;
    public BankAccounts $bankAccounts;
    public QrPayments $qr;
    public Transactions $transactions;
    public Webhooks $webhooks;
    public WebhookLogs $webhookLogs;

    public function __construct(
        string $username,
        string $password,
        ?string $clientSecret = null,
        string $baseUrl = 'https://api.monapay.vn',
        ?callable $transport = null,
        int $timeout = 30
    ) {
        if ($username === '' || $password === '') {
            throw new \InvalidArgumentException('username và password là bắt buộc');
        }
        $this->username = $username;
        $this->password = $password;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport = $transport;
        $this->timeout = $timeout;

        $this->keys = new Keys($this);
        $this->va = new VirtualAccounts($this);
        $this->bankAccounts = new BankAccounts($this);
        $this->qr = new QrPayments($this);
        $this->transactions = new Transactions($this);
        $this->webhooks = new Webhooks($this);
        $this->webhookLogs = new WebhookLogs($this);
    }

    /** @return mixed */
    public function me()
    {
        return $this->request('GET', '/api/v1/client/me');
    }

    /** @return \Generator<int,mixed> */
    public function iterTransactions(string $virtualAccountNumber, int $page = 1, int $limit = 100): \Generator
    {
        return $this->transactions->iterate($virtualAccountNumber, $page, $limit);
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param array<string,mixed>|null $body
     * @param array<string,mixed> $query
     * @return mixed
     */
    public function request(string $method, string $path, ?array $body = null, array $query = [])
    {
        if ($this->accessToken === null) {
            $this->login();
        }
        try {
            return $this->send($method, $path, $body, $query, true);
        } catch (ApiException $error) {
            if ($error->status !== 401) {
                throw $error;
            }
            $this->accessToken = null;
            $this->login();
            return $this->send($method, $path, $body, $query, true);
        }
    }

    private function login(): void
    {
        $data = $this->send(
            'POST',
            '/api/v1/client/login',
            ['username' => $this->username, 'password' => $this->password],
            [],
            false
        );
        if (!is_array($data) || empty($data['access_token'])) {
            throw new ApiException('Response đăng nhập không có access_token');
        }
        $this->accessToken = (string) $data['access_token'];
    }

    /**
     * @param array<string,mixed>|null $body
     * @param array<string,mixed> $query
     * @return mixed
     */
    private function send(string $method, string $path, ?array $body, array $query, bool $authenticated)
    {
        $query = array_filter($query, static function ($value): bool {
            return $value !== null;
        });
        $url = $this->baseUrl . $path;
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = ['Accept' => 'application/json'];
        if ($authenticated) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }
        if ($authenticated && $method !== 'GET' && $this->clientSecret !== null) {
            $headers['X-Client-Secret'] = $this->clientSecret;
        }
        $encodedBody = null;
        if ($body !== null) {
            $headers['Content-Type'] = 'application/json';
            $encodedBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            if ($encodedBody === false) {
                throw new ApiException('Không encode được request JSON: ' . json_last_error_msg());
            }
        }

        $request = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $encodedBody,
            'timeout' => $this->timeout,
        ];
        $response = $this->transport !== null
            ? call_user_func($this->transport, $request)
            : $this->curlTransport($request);
        if (!is_array($response) || !isset($response['status'])) {
            throw new ApiException('Transport phải trả về status và body');
        }
        $status = (int) $response['status'];
        $raw = $response['body'] ?? '';
        if (is_array($raw)) {
            $payload = $raw;
        } elseif ($raw === '' || $raw === null) {
            $payload = [];
        } else {
            $payload = json_decode((string) $raw, true);
            if (!is_array($payload)) {
                throw new ApiException(
                    'MONA Pay trả response không phải JSON (HTTP ' . $status . ')',
                    $status,
                    $raw
                );
            }
        }

        if ($status < 200 || $status >= 300 || (($payload['success'] ?? null) === false)) {
            $detail = isset($payload['detail']) && is_string($payload['detail']) ? $payload['detail'] : null;
            $message = $payload['message'] ?? $detail ?? ('MONA Pay API lỗi HTTP ' . $status);
            throw new ApiException((string) $message, $status, $payload);
        }
        return $payload['data'] ?? null;
    }

    /**
     * @param array{method:string,url:string,headers:array<string,string>,body:?string,timeout:int} $request
     * @return array{status:int,body:string}
     */
    private function curlTransport(array $request): array
    {
        if (!function_exists('curl_init')) {
            throw new ApiException('PHP extension curl chưa được bật');
        }
        $headerLines = [];
        foreach ($request['headers'] as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        $handle = curl_init($request['url']);
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $request['method'],
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $request['timeout'],
        ]);
        if ($request['body'] !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $request['body']);
        }
        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new ApiException('Không kết nối được MONA Pay: ' . $message);
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        return ['status' => $status, 'body' => (string) $body];
    }
}
