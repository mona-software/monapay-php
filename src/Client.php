<?php

declare(strict_types=1);

namespace MonaPay;

use MonaPay\Resources\BankAccounts;
use MonaPay\Resources\Checkouts;
use MonaPay\Resources\EmailConfigs;
use MonaPay\Resources\EmailLogs;
use MonaPay\Resources\EmailSuppressions;
use MonaPay\Resources\Keys;
use MonaPay\Resources\PaymentProfile;
use MonaPay\Resources\QrPayments;
use MonaPay\Resources\Sandbox;
use MonaPay\Resources\Transactions;
use MonaPay\Resources\VirtualAccounts;
use MonaPay\Resources\WebhookLogs;
use MonaPay\Resources\Webhooks;

final class Client
{
    private string $baseUrl;
    private ?string $clientId;
    private string $username;
    private string $password;
    private ?string $clientSecret;
    private ?string $accessToken = null;
    private float $tokenExpiresAt = 0.0;
    private int $timeout;

    /** @var callable|null */
    private $transport;

    public Keys $keys;
    public VirtualAccounts $va;
    public BankAccounts $bankAccounts;
    public PaymentProfile $paymentProfile;
    public Checkouts $checkouts;
    public QrPayments $qr;
    public Transactions $transactions;
    public Webhooks $webhooks;
    public WebhookLogs $webhookLogs;
    public Sandbox $sandbox;
    public EmailConfigs $emailConfigs;
    public EmailLogs $emailLogs;
    public EmailSuppressions $emailSuppressions;

    public function __construct(
        string $username,
        string $password,
        ?string $clientSecret = null,
        string $baseUrl = 'https://api.monapay.vn',
        ?callable $transport = null,
        int $timeout = 30,
        ?string $clientId = null
    ) {
        if (($clientId === null || $clientId === '' || $clientSecret === null || $clientSecret === '') && ($username === '' || $password === '')) {
            throw new \InvalidArgumentException('Cần client ID + client secret hoặc username + password; không dùng password cho AI agent vì sẽ gãy khi bật 2FA');
        }
        $this->clientId = $clientId;
        $this->username = $username;
        $this->password = $password;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport = $transport;
        $this->timeout = $timeout;

        $this->keys = new Keys($this);
        $this->va = new VirtualAccounts($this);
        $this->bankAccounts = new BankAccounts($this);
        $this->paymentProfile = new PaymentProfile($this);
        $this->checkouts = new Checkouts($this);
        $this->qr = new QrPayments($this);
        $this->transactions = new Transactions($this);
        $this->webhooks = new Webhooks($this);
        $this->webhookLogs = new WebhookLogs($this);
        $this->sandbox = new Sandbox($this);
        $this->emailConfigs = new EmailConfigs($this);
        $this->emailLogs = new EmailLogs($this);
        $this->emailSuppressions = new EmailSuppressions($this);
    }

    /** @param array<string,string>|null $env */
    public static function fromEnv(?array $env = null, ?callable $transport = null, int $timeout = 30): self
    {
        $read = static function (string $name) use ($env): ?string {
            if ($env !== null) {
                return $env[$name] ?? null;
            }
            $value = getenv($name);
            return $value === false ? null : $value;
        };
        return new self(
            $read('MONAPAY_USERNAME') ?? '',
            $read('MONAPAY_PASSWORD') ?? '',
            $read('MONAPAY_CLIENT_SECRET'),
            $read('MONAPAY_BASE_URL') ?? 'https://api.monapay.vn',
            $transport,
            $timeout,
            $read('MONAPAY_CLIENT_ID')
        );
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
     * @param array<string,string> $headers
     * @return mixed
     */
    public function request(string $method, string $path, ?array $body = null, array $query = [], array $headers = [])
    {
        if ($this->accessToken === null || microtime(true) >= $this->tokenExpiresAt) {
            $this->accessToken = null;
            $this->login();
        }
        try {
            return $this->send($method, $path, $body, $query, true, $headers);
        } catch (ApiException $error) {
            if ($error->status !== 401) {
                throw $error;
            }
            $this->accessToken = null;
            $this->tokenExpiresAt = 0.0;
            $this->login();
            return $this->send($method, $path, $body, $query, true, $headers);
        }
    }

    private function login(): void
    {
        $usingClientCredentials = $this->clientId !== null && $this->clientId !== '' && $this->clientSecret !== null && $this->clientSecret !== '';
        $data = $this->send(
            'POST',
            $usingClientCredentials ? '/api/v1/oauth/token' : '/api/v1/client/login',
            $usingClientCredentials
                ? ['grant_type' => 'client_credentials', 'client_id' => $this->clientId, 'client_secret' => $this->clientSecret]
                : ['username' => $this->username, 'password' => $this->password],
            [],
            false
        );
        if (!is_array($data) || empty($data['access_token'])) {
            throw new ApiException('Response đăng nhập không có access_token');
        }
        $this->accessToken = (string) $data['access_token'];
        $expiresIn = isset($data['expires_in']) && is_numeric($data['expires_in'])
            ? (float) $data['expires_in']
            : ($usingClientCredentials ? 3600.0 : 86400.0);
        $this->tokenExpiresAt = microtime(true) + max(0.0, $expiresIn - 60.0);
    }

    /**
     * @param array<string,mixed>|null $body
     * @param array<string,mixed> $query
     * @param array<string,string> $customHeaders
     * @return mixed
     */
    private function send(string $method, string $path, ?array $body, array $query, bool $authenticated, array $customHeaders = [])
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
        $headers = array_merge($headers, $customHeaders);
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
