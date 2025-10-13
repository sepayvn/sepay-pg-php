<?php

/**
 * SePay PHP SDK - Quick Start Guide
 *
 * This file shows a quick overview of SePay SDK features.
 * For detailed examples, check the numbered files:
 *
 * 01-checkout-basic.php      - Basic payments
 * 02-orders-management.php   - Order management
 * 03-payment-methods.php     - Payment methods
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\SePayException;

// Initialize client
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',
    SePayClient::ENVIRONMENT_SANDBOX
);

try {
    echo "=== SePay PHP SDK Quick Start ===\n\n";

    // Quick checkout example
    echo "1. Quick Checkout Example\n";
    echo "=========================\n";
    $checkoutData = CheckoutBuilder::make()
        ->currency('VND')
        ->orderAmount(100000)
        ->operation('PURCHASE')
        ->orderDescription('Quick test payment')
        ->orderInvoiceNumber('QUICK_' . time())
        ->successUrl('https://yoursite.com/success')
        ->build();

    $formFields = $sepay->checkout()->generateFormFields($checkoutData);
    echo "Checkout form generated with " . count($formFields) . " fields\n";
    echo "   Signature: " . substr($formFields['signature'], 0, 20) . "...\n";
    echo "   Submit to: " . $sepay->checkout()->getCheckoutUrl('sandbox') . "\n\n";

    // Quick orders check
    echo "2. Quick Orders Check\n";
    echo "=====================\n";
    $orders = $sepay->orders()->list(['per_page' => 3]);
    echo "✅ Found " . count($orders['data']) . " recent orders\n";
    if (!empty($orders['data'])) {
        $latestOrder = $orders['data'][0];
        echo "   Latest: {$latestOrder['order_invoice_number']} - {$latestOrder['order_status']}\n";
    }
    echo "\n";

    echo "=== Next Steps ===\n";
    echo "📁 Run detailed examples:\n";
    echo "   php examples/01-checkout-basic.php      # Basic payments\n";
    echo "   php examples/02-orders-management.php   # Orders management\n";
    echo "   php examples/03-payment-methods.php     # Payment methods\n\n";

    echo "📚 Key Features:\n";
    echo "   ✅ Checkout form generation with signatures\n";
    echo "   ✅ Orders management (list, retrieve, void)\n";
    echo "   ✅ Multiple payment methods (card, bank transfer)\n";
    echo "   ✅ Auto-merchant injection (set once, use everywhere)\n\n";

    echo "🔧 Configuration:\n";
    echo "   Environment: " . ($sepay->getEnvironment() ?? 'sandbox') . "\n";
    echo "   Merchant ID: Auto-injected from client\n";
    echo "   Base URL: " . $sepay->checkout()->getCheckoutUrl('sandbox') . "\n\n";

    echo "Quick start completed successfully!\n";
} catch (SePayException $e) {
    echo "SePay Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";

    if (method_exists($e, 'getErrorDetails') && $e->getErrorDetails()) {
        echo "Error Details: " . json_encode($e->getErrorDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
