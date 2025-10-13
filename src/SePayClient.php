<?php

declare(strict_types=1);

namespace SePay;

use SePay\Client\HttpClient;
use SePay\Resources\OrderResource;
use SePay\Resources\CheckoutResource;
use SePay\Auth\SignatureGenerator;
use SePay\Config\UrlConfig;
use SePay\Exceptions\SePayException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * SePay Payment Gateway PHP SDK
 *
 * Main client class providing access to all SePay API resources
 * with fluent interface and modern PHP patterns.
 *
 * @package SePay
 * @author Ngô Quốc Đạt <hi@ngoquocdat.dev>
 */
class SePayClient
{
    private string $merchantId;

    private string $secretKey;

    private string $environment;

    private HttpClient $httpClient;

    private LoggerInterface $logger;

    /** @var array<string, mixed> */
    private array $config;

    private ?string $customApiBaseUrl = null;

    private ?string $customCheckoutBaseUrl = null;

    private ?OrderResource $orders = null;

    private ?CheckoutResource $checkout = null;

    public const ENVIRONMENT_SANDBOX = UrlConfig::ENVIRONMENT_SANDBOX;

    public const ENVIRONMENT_PRODUCTION = UrlConfig::ENVIRONMENT_PRODUCTION;

    /**
     * Initialize SePay client
     *
     * @param string $merchantId Your SePay merchant ID
     * @param string $secretKey Your SePay secret key
     * @param string $environment Environment (sandbox|production)
     * @param array<string, mixed> $config Additional configuration options
     * @throws SePayException
     */
    public function __construct(
        string $merchantId,
        string $secretKey,
        string $environment = self::ENVIRONMENT_SANDBOX,
        array $config = []
    ) {
        $this->validateEnvironment($environment);

        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
        $this->environment = $environment;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->logger = $config['logger'] ?? new NullLogger();

        $this->httpClient = new HttpClient(
            $this->getApiBaseUrl(),
            $this->merchantId,
            $this->secretKey,
            $this->config,
            $this->logger,
        );
    }

    /**
     * Get Orders resource
     */
    public function orders(): OrderResource
    {
        if ($this->orders === null) {
            $this->orders = new OrderResource($this->httpClient, $this->logger);
        }

        return $this->orders;
    }

    /**
     * Get Checkout resource
     */
    public function checkout(): CheckoutResource
    {
        if ($this->checkout === null) {
            $this->checkout = new CheckoutResource($this->httpClient, $this->logger);

            $signatureGenerator = new SignatureGenerator($this->secretKey);
            $this->checkout->setSignatureGenerator($signatureGenerator);

            $this->checkout->setMerchantId($this->merchantId);
            $this->checkout->setCheckoutBaseUrl($this->getCheckoutBaseUrl());
        }

        return $this->checkout;
    }

    /**
     * Set logger instance
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        $this->httpClient->setLogger($logger);

        return $this;
    }

    /**
     * Enable debug mode
     */
    public function enableDebugMode(): self
    {
        $this->config['debug'] = true;
        $this->httpClient->setConfig($this->config);

        return $this;
    }

    /**
     * Set retry configuration
     */
    public function setRetryAttempts(int $attempts): self
    {
        $this->config['retry_attempts'] = $attempts;
        $this->httpClient->setConfig($this->config);

        return $this;
    }

    /**
     * Set retry delay in milliseconds
     */
    public function setRetryDelay(int $delayMs): self
    {
        $this->config['retry_delay'] = $delayMs;
        $this->httpClient->setConfig($this->config);

        return $this;
    }

    /**
     * Get current environment
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get merchant ID
     */
    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    /**
     * Get current configuration
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set custom API base URL
     */
    public function baseApiUrl(string $url): self
    {
        $this->customApiBaseUrl = rtrim($url, '/');
        $this->httpClient = new HttpClient(
            $this->getApiBaseUrl(),
            $this->merchantId,
            $this->secretKey,
            $this->config,
            $this->logger,
        );
        return $this;
    }

    /**
     * Set custom checkout base URL
     */
    public function baseCheckoutUrl(string $url): self
    {
        $this->customCheckoutBaseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Get API base URL for current environment
     */
    public function getApiBaseUrl(): string
    {
        return $this->customApiBaseUrl ?? UrlConfig::getApiBaseUrl($this->environment);
    }

    /**
     * Get checkout base URL for current environment
     */
    public function getCheckoutBaseUrl(): string
    {
        return $this->customCheckoutBaseUrl ?? UrlConfig::getCheckoutBaseUrl($this->environment);
    }

    /**
     * Validate environment parameter
     */
    private function validateEnvironment(string $environment): void
    {
        if (! in_array($environment, [self::ENVIRONMENT_SANDBOX, self::ENVIRONMENT_PRODUCTION], true)) {
            throw new SePayException(
                sprintf(
                    'Invalid environment "%s". Must be one of: %s',
                    $environment,
                    implode(', ', [self::ENVIRONMENT_SANDBOX, self::ENVIRONMENT_PRODUCTION]),
                ),
            );
        }
    }

    /**
     * Get default configuration
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
            'debug' => false,
            'user_agent' => 'SePay-PHP-SDK/1.0.0',
        ];
    }
}
