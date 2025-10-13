<?php

declare(strict_types=1);

namespace SePay\Config;

/**
 * URL configuration for SePay services
 *
 * Centralizes all URL management for SePay services:
 * - Base URLs for different environments
 * - API endpoints (/pgapi/)
 * - Checkout endpoints (/pay/)
 *
 * @package SePay\Config
 */
class UrlConfig
{
    public const ENVIRONMENT_SANDBOX = 'sandbox';

    public const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * API Base URLs
     */
    private const API_BASE_URLS = [
        self::ENVIRONMENT_SANDBOX => 'https://pgapi-sandbox.sepay.vn',
        self::ENVIRONMENT_PRODUCTION => 'https://pgapi.sepay.vn',
    ];

    /**
     * Checkout Base URLs
     */
    private const CHECKOUT_BASE_URLS = [
        self::ENVIRONMENT_SANDBOX => 'https://pay-sandbox.sepay.vn',
        self::ENVIRONMENT_PRODUCTION => 'https://pay.sepay.vn',
    ];

    /**
     * Get API base URL for environment
     */
    public static function getApiBaseUrl(string $environment): string
    {
        return self::API_BASE_URLS[$environment] ?? self::API_BASE_URLS[self::ENVIRONMENT_SANDBOX];
    }

    /**
     * Get checkout base URL for environment
     */
    public static function getCheckoutBaseUrl(string $environment): string
    {
        return self::CHECKOUT_BASE_URLS[$environment] ?? self::CHECKOUT_BASE_URLS[self::ENVIRONMENT_SANDBOX];
    }

    /**
     * Get full checkout URL for environment
     */
    public static function getCheckoutUrl(string $environment): string
    {
        $baseUrl = self::getCheckoutBaseUrl($environment);

        return $baseUrl . '/v1/checkout/init';
    }

    /**
     * Get all supported environments
     *
     * @return array<string>
     */
    public static function getSupportedEnvironments(): array
    {
        return [self::ENVIRONMENT_SANDBOX, self::ENVIRONMENT_PRODUCTION];
    }

    /**
     * Validate environment
     */
    public static function isValidEnvironment(string $environment): bool
    {
        return in_array($environment, self::getSupportedEnvironments(), true);
    }
}
