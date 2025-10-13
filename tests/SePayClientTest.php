<?php

declare(strict_types=1);

namespace SePay\Tests;

use PHPUnit\Framework\TestCase;
use SePay\SePayClient;
use SePay\Exceptions\SePayException;

/**
 * Test cases for SePayClient
 */
class SePayClientTest extends TestCase
{
    private SePayClient $client;

    protected function setUp(): void
    {
        $this->client = new SePayClient(
            'test-merchant-id',
            'test-secret-key',
            SePayClient::ENVIRONMENT_SANDBOX,
        );
    }

    public function testClientInitialization(): void
    {
        $this->assertInstanceOf(SePayClient::class, $this->client);
        $this->assertEquals('test-merchant-id', $this->client->getMerchantId());
        $this->assertEquals(SePayClient::ENVIRONMENT_SANDBOX, $this->client->getEnvironment());
    }

    public function testInvalidEnvironmentThrowsException(): void
    {
        $this->expectException(SePayException::class);
        $this->expectExceptionMessage('Invalid environment');

        new SePayClient('merchant', 'secret', 'invalid-env');
    }

    public function testResourcesAreAccessible(): void
    {
        $this->assertInstanceOf(\SePay\Resources\OrderResource::class, $this->client->orders());
        $this->assertInstanceOf(\SePay\Resources\CheckoutResource::class, $this->client->checkout());
    }

    public function testFluentConfiguration(): void
    {
        $client = $this->client
            ->enableDebugMode()
            ->setRetryAttempts(5)
            ->setRetryDelay(2000);

        $this->assertInstanceOf(SePayClient::class, $client);
    }

    public function testGetApiBaseUrl(): void
    {
        $apiUrl = $this->client->getApiBaseUrl();
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $apiUrl);
    }

    public function testGetCheckoutBaseUrl(): void
    {
        $checkoutUrl = $this->client->getCheckoutBaseUrl();
        $this->assertEquals('https://pay-sandbox.sepay.vn', $checkoutUrl);
    }

    public function testBaseApiUrl(): void
    {
        $customApiUrl = 'https://custom-api.example.com';
        $client = $this->client->baseApiUrl($customApiUrl);

        $this->assertInstanceOf(SePayClient::class, $client);
        $this->assertEquals($customApiUrl, $client->getApiBaseUrl());
        $this->assertEquals('https://pay-sandbox.sepay.vn', $client->getCheckoutBaseUrl());
    }

    public function testBaseCheckoutUrl(): void
    {
        $customCheckoutUrl = 'https://custom-checkout.example.com';
        $client = $this->client->baseCheckoutUrl($customCheckoutUrl);

        $this->assertInstanceOf(SePayClient::class, $client);
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $client->getApiBaseUrl());
        $this->assertEquals($customCheckoutUrl, $client->getCheckoutBaseUrl());
    }

    public function testBothCustomUrls(): void
    {
        $customApiUrl = 'https://api.mycompany.com';
        $customCheckoutUrl = 'https://checkout.mycompany.com';

        $client = $this->client
            ->baseApiUrl($customApiUrl)
            ->baseCheckoutUrl($customCheckoutUrl);

        $this->assertInstanceOf(SePayClient::class, $client);
        $this->assertEquals($customApiUrl, $client->getApiBaseUrl());
        $this->assertEquals($customCheckoutUrl, $client->getCheckoutBaseUrl());
    }

    public function testFluentUrlConfigurationChain(): void
    {
        $client = $this->client
            ->baseApiUrl('https://api.staging.example.com')
            ->baseCheckoutUrl('https://checkout.staging.example.com')
            ->enableDebugMode()
            ->setRetryAttempts(5);

        $this->assertInstanceOf(SePayClient::class, $client);
        $this->assertEquals('https://api.staging.example.com', $client->getApiBaseUrl());
        $this->assertEquals('https://checkout.staging.example.com', $client->getCheckoutBaseUrl());
        $this->assertTrue($client->getConfig()['debug']);
        $this->assertEquals(5, $client->getConfig()['retry_attempts']);
    }

    public function testGetConfig(): void
    {
        $config = $this->client->getConfig();

        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('retry_attempts', $config);
        $this->assertArrayHasKey('retry_delay', $config);
        $this->assertArrayHasKey('debug', $config);
        $this->assertArrayHasKey('user_agent', $config);
    }

    public function testProductionEnvironmentUrls(): void
    {
        $prodClient = new SePayClient(
            'test-merchant-id',
            'test-secret-key',
            SePayClient::ENVIRONMENT_PRODUCTION,
        );

        $this->assertEquals('https://pgapi.sepay.vn', $prodClient->getApiBaseUrl());
        $this->assertEquals('https://pay.sepay.vn', $prodClient->getCheckoutBaseUrl());
    }

    public function testBaseApiUrlTrimsTrailingSlash(): void
    {
        $client = $this->client->baseApiUrl('https://api.example.com/');
        $this->assertEquals('https://api.example.com', $client->getApiBaseUrl());
    }

    public function testBaseCheckoutUrlTrimsTrailingSlash(): void
    {
        $client = $this->client->baseCheckoutUrl('https://checkout.example.com/');
        $this->assertEquals('https://checkout.example.com', $client->getCheckoutBaseUrl());
    }
}
