<?php

/**
 * Example 5: Different Payment Methods
 *
 * Shows how to create checkout forms for different payment methods:
 * - Card payments
 * - Bank transfer payments
 * - Auto-select payment method
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\SePayException;

// Initialize SePay client with default URLs
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',
    SePayClient::ENVIRONMENT_SANDBOX
);

// Optional: Use custom base URLs with fluent interface
// $sepay = $sepay
//     ->baseApiUrl('https://custom-api.example.com')
//     ->baseCheckoutUrl('https://custom-checkout.example.com');

try {
    echo "=== Payment Methods Example ===\n\n";

    // 1. Card Payment
    echo "1. Card Payment Checkout\n";
    echo "========================\n";

    $cardCheckout = CheckoutBuilder::make()
        ->paymentMethod('CARD')
        ->currency('VND')
        ->orderAmount(250000)  // 250,000 VND
        ->operation('PURCHASE')
        ->orderDescription('Premium service payment via card')
        ->orderInvoiceNumber('CARD_' . time())
        ->customerId('customer_card_001')
        ->successUrl('https://yoursite.com/card/success')
        ->errorUrl('https://yoursite.com/card/error')
        ->cancelUrl('https://yoursite.com/card/cancel')
        ->build();

    $cardFields = $sepay->checkout()->generateFormFields($cardCheckout);

    echo "Card payment form fields:\n";
    foreach ($cardFields as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";

    // 2. Bank Transfer Payment
    echo "2. Bank Transfer Payment Checkout\n";
    echo "=================================\n";

    $bankCheckout = CheckoutBuilder::make()
        ->paymentMethod('BANK_TRANSFER')
        ->currency('VND')
        ->orderAmount(150000)  // 150,000 VND
        ->operation('PURCHASE')
        ->orderDescription('Service payment via bank transfer')
        ->orderInvoiceNumber('BANK_' . time())
        ->customerId('customer_bank_001')
        ->successUrl('https://yoursite.com/bank/success')
        ->errorUrl('https://yoursite.com/bank/error')
        ->cancelUrl('https://yoursite.com/bank/cancel')
        ->build();

    $bankFields = $sepay->checkout()->generateFormFields($bankCheckout);

    echo "Bank transfer form fields:\n";
    foreach ($bankFields as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";

    // 3. Auto-select Payment Method
    echo "3. Auto-select Payment Method\n";
    echo "=============================\n";

    $autoCheckout = CheckoutBuilder::make()
        // No paymentMethod() specified - user can choose
        ->currency('VND')
        ->orderAmount(75000)  // 75,000 VND
        ->operation('PURCHASE')
        ->orderDescription('Flexible payment - user chooses method')
        ->orderInvoiceNumber('AUTO_' . time())
        ->customerId('customer_auto_001')
        ->successUrl('https://yoursite.com/auto/success')
        ->errorUrl('https://yoursite.com/auto/error')
        ->cancelUrl('https://yoursite.com/auto/cancel')
        ->build();

    $autoFields = $sepay->checkout()->generateFormFields($autoCheckout);

    echo "Auto-select payment form fields:\n";
    foreach ($autoFields as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";

    // 4. Generate HTML forms for each method
    echo "4. HTML Forms for Each Payment Method\n";
    echo "=====================================\n";

    echo "Card Payment Form:\n";
    $cardForm = $sepay->checkout()->generateFormHtml(
        $cardCheckout,
        'sandbox',
        ['id' => 'card-payment-form', 'class' => 'payment-form card-form']
    );
    echo $cardForm . "\n\n";

    echo "Bank Transfer Form:\n";
    $bankForm = $sepay->checkout()->generateFormHtml(
        $bankCheckout,
        'sandbox',
        ['id' => 'bank-payment-form', 'class' => 'payment-form bank-form']
    );
    echo $bankForm . "\n\n";

    echo "Auto-select Form:\n";
    $autoForm = $sepay->checkout()->generateFormHtml(
        $autoCheckout,
        'sandbox',
        ['id' => 'auto-payment-form', 'class' => 'payment-form auto-form']
    );
    echo $autoForm . "\n\n";

    // 5. Payment method comparison
    echo "5. Payment Method Comparison\n";
    echo "============================\n";

    echo "CARD Payment:\n";
    echo "  ✅ Instant processing\n";
    echo "  ✅ Good for recurring payments\n";
    echo "  ✅ International cards supported\n";
    echo "  ⚠️  Requires card details\n";
    echo "  💳 Best for: Online purchases, subscriptions\n\n";

    echo "BANK_TRANSFER Payment:\n";
    echo "  ✅ Direct bank account transfer\n";
    echo "  ✅ No card required\n";
    echo "  ✅ Good for large amounts\n";
    echo "  ⚠️  May take longer to process\n";
    echo "  🏦 Best for: Large purchases, B2B payments\n\n";

    echo "Auto-select (No method specified):\n";
    echo "  ✅ User chooses preferred method\n";
    echo "  ✅ Maximum flexibility\n";
    echo "  ✅ Better user experience\n";
    echo "  ✅ Supports all available methods\n";
    echo "  🎯 Best for: General e-commerce\n\n";

    echo "=== Implementation Tips ===\n";
    echo "• Use CARD for recurring payments and subscriptions\n";
    echo "• Use BANK_TRANSFER for high-value transactions\n";
    echo "• Use auto-select for general e-commerce flexibility\n";
    echo "• Always provide clear success/error/cancel URLs\n";
    echo "• Test each payment method in sandbox environment\n";
    echo "• Consider user preferences and transaction amounts\n\n";

    echo "=== Security Notes ===\n";
    echo "• All payment methods use HMAC-SHA256 signatures\n";
    echo "• Sensitive data is handled by SePay, not your server\n";
    echo "• Always use HTTPS for your callback URLs\n";
    echo "• Validate payment status using Orders API\n\n";

    echo "Payment methods examples completed successfully!\n";
} catch (SePayException $e) {
    echo "SePay Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
