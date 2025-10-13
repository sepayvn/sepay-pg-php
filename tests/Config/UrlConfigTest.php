<?php

declare(strict_types=1);

namespace SePay\Tests\Config;

use PHPUnit\Framework\TestCase;
use SePay\Config\UrlConfig;

class UrlConfigTest extends TestCase
{
    public function testEnvironmentConstants(): void
    {
        $this->assertEquals('sandbox', UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals('production', UrlConfig::ENVIRONMENT_PRODUCTION);
    }

    public function testGetApiBaseUrlSandbox(): void
    {
        $url = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $url);
    }

    public function testGetApiBaseUrlProduction(): void
    {
        $url = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertEquals('https://pgapi.sepay.vn', $url);
    }

    public function testGetCheckoutBaseUrlSandbox(): void
    {
        $url = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals('https://pay-sandbox.sepay.vn', $url);
    }

    public function testGetCheckoutBaseUrlProduction(): void
    {
        $url = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertEquals('https://pay.sepay.vn', $url);
    }

    public function testGetBaseUrlSandbox(): void
    {
        $url = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals('https://pgapi-sandbox.sepay.vn', $url);
    }

    public function testGetBaseUrlProduction(): void
    {
        $url = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertEquals('https://pgapi.sepay.vn', $url);
    }

    public function testGetBaseUrlFallback(): void
    {
        $url = UrlConfig::getApiBaseUrl('invalid_environment');
        $expectedUrl = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetCheckoutUrlSandbox(): void
    {
        $url = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertEquals('https://pay-sandbox.sepay.vn/v1/checkout/init', $url);
    }

    public function testGetCheckoutUrlProduction(): void
    {
        $url = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertEquals('https://pay.sepay.vn/v1/checkout/init', $url);
    }

    public function testGetCheckoutUrlFallback(): void
    {
        $url = UrlConfig::getCheckoutUrl('invalid_environment');
        $this->assertEquals('https://pay-sandbox.sepay.vn/v1/checkout/init', $url);
    }

    public function testGetSupportedEnvironments(): void
    {
        $environments = UrlConfig::getSupportedEnvironments();
        $this->assertContains(UrlConfig::ENVIRONMENT_SANDBOX, $environments);
        $this->assertContains(UrlConfig::ENVIRONMENT_PRODUCTION, $environments);
        $this->assertCount(2, $environments);
    }

    public function testIsValidEnvironment(): void
    {
        $this->assertTrue(UrlConfig::isValidEnvironment(UrlConfig::ENVIRONMENT_SANDBOX));
        $this->assertTrue(UrlConfig::isValidEnvironment(UrlConfig::ENVIRONMENT_PRODUCTION));
        $this->assertFalse(UrlConfig::isValidEnvironment('invalid'));
        $this->assertFalse(UrlConfig::isValidEnvironment(''));
    }

    public function testUrlStructure(): void
    {
        $sandboxApiUrl = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $productionApiUrl = UrlConfig::getApiBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertNotEquals($sandboxApiUrl, $productionApiUrl);

        $sandboxCheckoutUrl = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $productionCheckoutUrl = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertNotEquals($sandboxCheckoutUrl, $productionCheckoutUrl);

        $sandboxCheckout = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $productionCheckout = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_PRODUCTION);

        $this->assertStringEndsWith('/v1/checkout/init', $sandboxCheckout);
        $this->assertStringEndsWith('/v1/checkout/init', $productionCheckout);
    }

    public function testUrlConsistency(): void
    {
        $sandboxCheckoutBase = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $sandboxCheckout = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_SANDBOX);
        $this->assertStringStartsWith($sandboxCheckoutBase, $sandboxCheckout);

        $productionCheckoutBase = UrlConfig::getCheckoutBaseUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $productionCheckout = UrlConfig::getCheckoutUrl(UrlConfig::ENVIRONMENT_PRODUCTION);
        $this->assertStringStartsWith($productionCheckoutBase, $productionCheckout);
    }
}
