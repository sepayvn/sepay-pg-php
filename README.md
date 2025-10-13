# SePay PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![PHP Version](https://img.shields.io/packagist/php-v/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)
[![License](https://img.shields.io/packagist/l/sepay/sepay-pg.svg)](https://packagist.org/packages/sepay/sepay-pg)

**Translations:** [English](README.md) | [Tiếng Việt](README.vi.md)

Official PHP SDK for SePay Payment Gateway. Easy integration for payments, bank transfers, VietQR.

## Installation

Install via Composer:

```bash
composer require sepay/sepay-pg
```

## Requirements

- PHP 7.4 or higher
- ext-json
- ext-curl
- Guzzle HTTP client

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;

// Initialize client
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',
    SePayClient::ENVIRONMENT_SANDBOX // or ENVIRONMENT_PRODUCTION
);

// Create checkout
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000) // 100,000 VND
    ->operation('PURCHASE')
    ->orderDescription('Test payment')
    ->orderInvoiceNumber('INV_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Generate form fields with signature
$formFields = $sepay->checkout()->generateFormFields($checkoutData);

echo "Checkout form created with signature: " . $formFields['signature'];
```

## Examples

Run the examples to see how everything works:

```bash
# Overview
php examples/basic-usage.php

# Specific examples
php examples/01-checkout-basic.php      # Basic payments
php examples/02-orders-management.php   # Orders management
php examples/03-payment-methods.php     # Payment methods
```

## Core Concepts

### Client Initialization

```php
use SePay\SePayClient;

$sepay = new SePayClient(
    $merchantId,
    $secretKey,
    $environment, // 'sandbox' or 'production'
    $config       // Optional configuration array
);

// Enable debug mode
$sepay->enableDebugMode();

// Configure retry behavior
$sepay->setRetryAttempts(3)
      ->setRetryDelay(1000); // milliseconds
```

### Builder Pattern

You can use the CheckoutBuilder for a clean API, or just pass arrays directly.

```php
// Using CheckoutBuilder (recommended)
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000)
    ->operation('PURCHASE')
    ->orderDescription('Test payment')
    ->build();

// Using array directly (good for dynamic data)
$checkoutArray = [
    'currency' => 'VND',
    'order_amount' => 100000,
    'operation' => 'PURCHASE',
    'order_description' => 'Test payment',
];

// Both work with generateFormFields
$formFields = $sepay->checkout()->generateFormFields($checkoutData);
$formFields = $sepay->checkout()->generateFormFields($checkoutArray);
```

## API Resources

### Checkout

Generate secure form fields for payment processing. This is how you start payments with SePay.

```php
// Simple checkout using CheckoutBuilder
$checkoutData = CheckoutBuilder::make()
    ->currency('VND')
    ->orderAmount(100000) // 100,000 VND
    ->operation('PURCHASE')
    ->orderDescription('Payment for order #123')
    ->orderInvoiceNumber('INV_123')
    ->customerId('customer_001')
    ->successUrl('https://yoursite.com/success')
    ->errorUrl('https://yoursite.com/error')
    ->cancelUrl('https://yoursite.com/cancel')
    ->build();

// Generate form fields with signature
$formFields = $sepay->checkout()->generateFormFields($checkoutData);

// Alternative: Pass array directly
$checkoutArray = [
    'currency' => 'VND',
    'order_amount' => 100000,
    'operation' => 'PURCHASE',
    'order_description' => 'Payment for order #123',
    'order_invoice_number' => 'INV_123',
    'customer_id' => 'customer_001',
    'success_url' => 'https://yoursite.com/success',
    'error_url' => 'https://yoursite.com/error',
    'cancel_url' => 'https://yoursite.com/cancel',
];

$formFields = $sepay->checkout()->generateFormFields($checkoutArray);
```

#### Two Ways to Use generateFormFields

**Method 1: Using CheckoutBuilder**

- Type-safe and clean API
- Built-in validation
- Good IDE support
- Easy to read

**Method 2: Using Arrays**

- More flexible
- Good for dynamic data
- Easy to integrate with existing code
- Same validation and security

```php
// Get checkout endpoint URL
$checkoutUrl = $sepay->checkout()->getCheckoutUrl('sandbox'); // or 'production'

// Generate complete HTML form
$htmlForm = $sepay->checkout()->generateFormHtml($checkoutData, 'sandbox', [
    'id' => 'payment-form',
    'class' => 'checkout-form',
]);
```

#### Payment Methods

```php
// Card payment using CheckoutBuilder
$cardCheckout = CheckoutBuilder::make()
    ->paymentMethod('CARD')
    ->currency('VND')
    ->orderAmount(250000)
    ->operation('PURCHASE')
    ->orderDescription('Card payment')
    ->orderInvoiceNumber('CARD_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Bank transfer payment using CheckoutBuilder
$bankCheckout = CheckoutBuilder::make()
    ->paymentMethod('BANK_TRANSFER')
    ->currency('VND')
    ->orderAmount(150000)
    ->operation('PURCHASE')
    ->orderDescription('Bank transfer payment')
    ->orderInvoiceNumber('BANK_001')
    ->successUrl('https://yoursite.com/success')
    ->build();

// Alternative: Using arrays directly
$cardArray = [
    'payment_method' => 'CARD',
    'currency' => 'VND',
    'order_amount' => 250000,
    'operation' => 'PURCHASE',
    'order_description' => 'Card payment',
    'order_invoice_number' => 'CARD_001',
    'success_url' => 'https://yoursite.com/success',
];

$bankArray = [
    'payment_method' => 'BANK_TRANSFER',
    'currency' => 'VND',
    'order_amount' => 150000,
    'operation' => 'PURCHASE',
    'order_description' => 'Bank transfer payment',
    'order_invoice_number' => 'BANK_001',
    'branch_code' => '001',
    'success_url' => 'https://yoursite.com/success',
];

// Both methods work the same way
$cardFields = $sepay->checkout()->generateFormFields($cardArray);
$bankFields = $sepay->checkout()->generateFormFields($bankArray);
```

### Orders

```php
// Retrieve order by invoice number
$order = $sepay->orders()->retrieve('order_invoice_number');

// List orders with filters
$orders = $sepay->orders()->list([
    'per_page' => 10,
    'order_status' => 'CAPTURED',
    'start_created_at' => '2024-01-01',
    'end_created_at' => '2024-12-31',
]);

// Void transaction (cancel payment)
$result = $sepay->orders()->voidTransaction('order_invoice_number');
```

Note: Orders are created when customers complete checkout, not directly through the API.

## Error Handling

The SDK has different exception types for different errors:

```php
use SePay\Exceptions\AuthenticationException;
use SePay\Exceptions\ValidationException;
use SePay\Exceptions\NotFoundException;
use SePay\Exceptions\RateLimitException;
use SePay\Exceptions\ServerException;

try {
    $order = $sepay->orders()->retrieve('order_invoice_number');
} catch (AuthenticationException $e) {
    // Invalid credentials or signature
    echo "Authentication failed: " . $e->getMessage();
} catch (ValidationException $e) {
    // Invalid request data
    echo "Validation error: " . $e->getMessage();

    // Get field-specific errors
    if ($e->hasFieldError('amount')) {
        $errors = $e->getFieldErrors('amount');
        echo "Amount errors: " . implode(', ', $errors);
    }
} catch (NotFoundException $e) {
    // Resource not found
    echo "Not found: " . $e->getMessage();
} catch (RateLimitException $e) {
    // Rate limit exceeded
    echo "Rate limited. Retry after: " . $e->getRetryAfter() . " seconds";
} catch (ServerException $e) {
    // Server error (5xx)
    echo "Server error: " . $e->getMessage();
}
```

## Configuration

### Environment Variables

```php
// You can use environment variables for configuration
$sepay = new SePayClient(
    $_ENV['SEPAY_MERCHANT_ID'],
    $_ENV['SEPAY_SECRET_KEY'],
    $_ENV['SEPAY_ENVIRONMENT'] ?? SePayClient::ENVIRONMENT_SANDBOX
);
```

### Custom Configuration

```php
$config = [
    'timeout' => 60,           // Request timeout in seconds
    'retry_attempts' => 5,     // Number of retry attempts
    'retry_delay' => 2000,     // Delay between retries in milliseconds
    'debug' => true,           // Enable debug logging
    'user_agent' => 'MyApp/1.0 SePay-PHP-SDK/1.0.0',
    'logger' => $customLogger, // PSR-3 compatible logger
];

$sepay = new SePayClient($merchantId, $secretKey, $environment, $config);
```

## Testing

Run the test suite:

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Run static analysis
composer phpstan

# Fix code style
composer cs-fix
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## License

This SDK is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Support

- Email: <info@sepay.vn>
- Documentation: <https://docs.sepay.vn>
- Issues: <https://github.com/sepayvn/sepay-pg-php/issues>

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and changes.
