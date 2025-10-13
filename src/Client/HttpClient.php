<?php

declare(strict_types=1);

namespace SePay\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SePay\Exceptions\AuthenticationException;
use SePay\Exceptions\RateLimitException;
use SePay\Exceptions\ServerException;
use SePay\Exceptions\ValidationException;
use SePay\Exceptions\SePayException;

/**
 * HTTP Client for SePay API
 *
 * Handles authentication, request signing, retry logic, and error handling
 *
 * @package SePay\Client
 */
class HttpClient
{
    private Client $client;

    private string $baseUrl;

    private string $merchantId;

    private string $secretKey;

    /** @var array<string, mixed> */
    private array $config;

    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        string $baseUrl,
        string $merchantId,
        string $secretKey,
        array $config = [],
        ?LoggerInterface $logger = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();

        $this->client = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'headers' => [
                'User-Agent' => $config['user_agent'] ?? 'SePay-PHP-SDK/1.0.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Make GET request
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Make POST request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make PUT request
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make DELETE request
     *
     * @return array<string, mixed>
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make HTTP request with retry logic
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $endpoint, array $options = []): array
    {
        $url = $this->baseUrl . '/v1/' . ltrim($endpoint, '/');
        $attempts = 0;
        $maxAttempts = $this->config['retry_attempts'] ?? 3;

        while ($attempts < $maxAttempts) {
            try {
                $attempts++;

                $options['headers'] = array_merge(
                    $options['headers'] ?? [],
                    $this->getAuthHeaders(),
                );

                $this->logger->debug('SePay API Request', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempts,
                    'options' => $this->sanitizeLogData($options),
                ]);

                $response = $this->client->request($method, $url, $options);

                $this->logger->debug('SePay API Response', [
                    'status_code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                ]);

                return $this->handleResponse($response);
            } catch (RequestException $e) {
                $this->logger->error('SePay API Request Failed', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
                ]);

                if ($attempts >= $maxAttempts || ! $this->shouldRetry($e)) {
                    throw $this->handleException($e);
                }

                $delay = $this->config['retry_delay'] ?? 1000;
                usleep($delay * 1000);
            } catch (GuzzleException $e) {
                $this->logger->error('SePay API HTTP Error', [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                throw new SePayException('HTTP request failed: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new SePayException('Maximum retry attempts exceeded');
    }

    /**
     * Get authentication headers
     *
     * @return array<string, string>
     */
    private function getAuthHeaders(): array
    {
        $credentials = base64_encode($this->merchantId . ':' . $this->secretKey);

        return [
            'Authorization' => 'Basic ' . $credentials,
        ];
    }

    /**
     * Handle HTTP response
     *
     * @return array<string, mixed>
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        if ($statusCode >= 400) {
            throw new SePayException("HTTP {$statusCode}: {$body}", $statusCode);
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SePayException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * Handle exceptions and convert to appropriate SePay exceptions
     */
    private function handleException(RequestException $e): SePayException
    {
        $statusCode = $e->getCode();
        $message = $e->getMessage();

        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['message'])) {
                $message = $data['message'];
            }
        }

        switch ($statusCode) {
            case 401:
                return new AuthenticationException($message, $statusCode, $e);
            case 400:
                return new ValidationException($message, $statusCode, $e);
            case 429:
                return new RateLimitException($message, $statusCode, $e);
            case $statusCode >= 500:
                return new ServerException($message, $statusCode, $e);
            default:
                return new SePayException($message, $statusCode, $e);
        }
    }

    /**
     * Determine if request should be retried
     */
    private function shouldRetry(RequestException $e): bool
    {
        if (! $e->hasResponse()) {
            return true;
        }

        $statusCode = $e->getResponse()->getStatusCode();

        return $statusCode >= 500 || $statusCode === 429;
    }

    /**
     * Sanitize data for logging (remove sensitive information)
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeLogData(array $data): array
    {
        $sanitized = $data;

        if (isset($sanitized['headers'])) {
            unset($sanitized['headers']['Authorization']);
        }

        return $sanitized;
    }

    /**
     * Set logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Set configuration
     *
     * @param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}
