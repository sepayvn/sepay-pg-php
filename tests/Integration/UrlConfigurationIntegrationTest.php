<?php

declare(strict_types=1);

namespace SePay\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Mockery;
use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Config\UrlConfig;

class UrlConfigurationIntegrationTest extends TestCase
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

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDefaultUrlConfiguration(): void
    {
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $this->client->getApiBaseUrl());
        $this->assertEquals('https://pay-sandbox.sepay.vn', $this->client->getCheckoutBaseUrl());

        $checkout = $this->client->checkout();
        $this->assertEquals('https://pay-sandbox.sepay.vn/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testCustomApiUrlConfiguration(): void
    {
        $customApiUrl = 'https://api.mycompany.com';
        $client = $this->client->baseApiUrl($customApiUrl);

        $this->assertEquals($customApiUrl, $client->getApiBaseUrl());

        $this->assertEquals('https://pay-sandbox.sepay.vn', $client->getCheckoutBaseUrl());

        $checkout = $client->checkout();
        $this->assertEquals('https://pay-sandbox.sepay.vn/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testCustomCheckoutUrlConfiguration(): void
    {
        $customCheckoutUrl = 'https://checkout.mycompany.com';
        $client = $this->client->baseCheckoutUrl($customCheckoutUrl);

        $this->assertEquals($customCheckoutUrl, $client->getCheckoutBaseUrl());

        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $client->getApiBaseUrl());

        $checkout = $client->checkout();
        $this->assertEquals($customCheckoutUrl . '/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testBothCustomUrlsConfiguration(): void
    {
        $customApiUrl = 'https://api.mycompany.com';
        $customCheckoutUrl = 'https://checkout.mycompany.com';

        $client = $this->client
            ->baseApiUrl($customApiUrl)
            ->baseCheckoutUrl($customCheckoutUrl);

        $this->assertEquals($customApiUrl, $client->getApiBaseUrl());
        $this->assertEquals($customCheckoutUrl, $client->getCheckoutBaseUrl());

        $checkout = $client->checkout();
        $this->assertEquals($customCheckoutUrl . '/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testFluentConfigurationChain(): void
    {
        $client = $this->client
            ->baseApiUrl('https://api.staging.example.com')
            ->baseCheckoutUrl('https://checkout.staging.example.com')
            ->enableDebugMode()
            ->setRetryAttempts(5);

        $this->assertEquals('https://api.staging.example.com', $client->getApiBaseUrl());
        $this->assertEquals('https://checkout.staging.example.com', $client->getCheckoutBaseUrl());
        $this->assertTrue($client->getConfig()['debug']);
        $this->assertEquals(5, $client->getConfig()['retry_attempts']);

        $checkout = $client->checkout();
        $this->assertEquals('https://checkout.staging.example.com/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testUrlConfigurationWithFormGeneration(): void
    {
        $customCheckoutUrl = 'https://pay.mycompany.com';
        $client = $this->client->baseCheckoutUrl($customCheckoutUrl);

        $checkoutData = CheckoutBuilder::make()
            ->currency('VND')
            ->orderAmount(100000)
            ->operation('PURCHASE')
            ->orderDescription('Test payment with custom URL')
            ->orderInvoiceNumber('TEST_' . time())
            ->customerId('customer_001')
            ->successUrl('https://yoursite.com/success')
            ->errorUrl('https://yoursite.com/error')
            ->cancelUrl('https://yoursite.com/cancel')
            ->build();

        $formFields = $client->checkout()->generateFormFields($checkoutData);

        $this->assertArrayHasKey('merchant', $formFields);
        $this->assertArrayHasKey('currency', $formFields);
        $this->assertArrayHasKey('order_amount', $formFields);
        $this->assertArrayHasKey('operation', $formFields);
        $this->assertArrayHasKey('signature', $formFields);

        $html = $client->checkout()->generateFormHtml($checkoutData, 'sandbox', [
            'id' => 'custom-checkout-form',
            'class' => 'payment-form',
        ]);

        $expectedActionUrl = $customCheckoutUrl . '/v1/checkout/init';
        $this->assertStringContainsString('action="' . $expectedActionUrl . '"', $html);
        $this->assertStringContainsString('id="custom-checkout-form"', $html);
        $this->assertStringContainsString('class="payment-form"', $html);
        $this->assertStringContainsString('name="merchant"', $html);
        $this->assertStringContainsString('name="signature"', $html);
    }

    public function testProductionEnvironmentWithCustomUrls(): void
    {
        $client = new SePayClient(
            'test-merchant-id',
            'test-secret-key',
            SePayClient::ENVIRONMENT_PRODUCTION,
        );

        $this->assertEquals('https://pgapi.sepay.vn', $client->getApiBaseUrl());
        $this->assertEquals('https://pay.sepay.vn', $client->getCheckoutBaseUrl());

        $client = $client
            ->baseApiUrl('https://api.prod.mycompany.com')
            ->baseCheckoutUrl('https://checkout.prod.mycompany.com');

        $this->assertEquals('https://api.prod.mycompany.com', $client->getApiBaseUrl());
        $this->assertEquals('https://checkout.prod.mycompany.com', $client->getCheckoutBaseUrl());

        $checkout = $client->checkout();
        $this->assertEquals('https://checkout.prod.mycompany.com/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testUrlTrimming(): void
    {
        $client = $this->client
            ->baseApiUrl('https://api.example.com/')
            ->baseCheckoutUrl('https://checkout.example.com/');

        $this->assertEquals('https://api.example.com', $client->getApiBaseUrl());
        $this->assertEquals('https://checkout.example.com', $client->getCheckoutBaseUrl());

        $checkout = $client->checkout();
        $this->assertEquals('https://checkout.example.com/v1/checkout/init', $checkout->getCheckoutUrl());
    }

    public function testUrlConfigurationPersistence(): void
    {
        $client = $this->client
            ->baseApiUrl('https://api.persistent.com')
            ->baseCheckoutUrl('https://checkout.persistent.com');

        $this->assertEquals('https://api.persistent.com', $client->getApiBaseUrl());
        $this->assertEquals('https://checkout.persistent.com', $client->getCheckoutBaseUrl());

        $checkout1 = $client->checkout();
        $checkout2 = $client->checkout();

        $this->assertEquals('https://checkout.persistent.com/v1/checkout/init', $checkout1->getCheckoutUrl());
        $this->assertEquals('https://checkout.persistent.com/v1/checkout/init', $checkout2->getCheckoutUrl());
    }

    public function testUrlConfigurationWithDifferentEnvironments(): void
    {
        $sandboxClient = new SePayClient(
            'test-merchant-id',
            'test-secret-key',
            SePayClient::ENVIRONMENT_SANDBOX,
        );

        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $sandboxClient->getApiBaseUrl());
        $this->assertEquals('https://pay-sandbox.sepay.vn', $sandboxClient->getCheckoutBaseUrl());

        $prodClient = new SePayClient(
            'test-merchant-id',
            'test-secret-key',
            SePayClient::ENVIRONMENT_PRODUCTION,
        );

        $this->assertEquals('https://pgapi.sepay.vn', $prodClient->getApiBaseUrl());
        $this->assertEquals('https://pay.sepay.vn', $prodClient->getCheckoutBaseUrl());

        $customSandboxClient = $sandboxClient
            ->baseApiUrl('https://api.sandbox.mycompany.com')
            ->baseCheckoutUrl('https://checkout.sandbox.mycompany.com');

        $customProdClient = $prodClient
            ->baseApiUrl('https://api.prod.mycompany.com')
            ->baseCheckoutUrl('https://checkout.prod.mycompany.com');

        $this->assertEquals('https://api.sandbox.mycompany.com', $customSandboxClient->getApiBaseUrl());
        $this->assertEquals('https://checkout.sandbox.mycompany.com', $customSandboxClient->getCheckoutBaseUrl());

        $this->assertEquals('https://api.prod.mycompany.com', $customProdClient->getApiBaseUrl());
        $this->assertEquals('https://checkout.prod.mycompany.com', $customProdClient->getCheckoutBaseUrl());
    }

    public function testBackwardCompatibility(): void
    {
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $this->client->getApiBaseUrl());

        $this->assertEquals('https://pgapi-sandbox.sepay.vn', UrlConfig::getApiBaseUrl('sandbox'));
        $this->assertEquals('https://pay-sandbox.sepay.vn/v1/checkout/init', UrlConfig::getCheckoutUrl('sandbox'));

        $this->assertEquals('https://pgapi-sandbox.sepay.vn', UrlConfig::getApiBaseUrl('sandbox'));
        $this->assertEquals('https://pay-sandbox.sepay.vn', UrlConfig::getCheckoutBaseUrl('sandbox'));
    }
}
