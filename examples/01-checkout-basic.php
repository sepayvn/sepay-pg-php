<?php

/**
 * Example 1: Basic Checkout
 *
 * Shows how to create a simple one-time payment checkout form.
 * This is the most common way to use SePay.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SePay\SePayClient;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\SePayException;

// Initialize SePay client
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',        // Your merchant ID
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',        // Your secret key
    SePayClient::ENVIRONMENT_SANDBOX  // Use ENVIRONMENT_PRODUCTION for live
);

try {
    echo "=== Basic Checkout Example ===\n\n";

    // Create basic checkout data
    // Note: merchant ID is automatically set from client, no need to specify again
    $checkoutData = CheckoutBuilder::make()
        ->currency('VND')
        ->orderAmount(100000)  // 100,000 VND
        ->operation('PURCHASE')
        ->orderDescription('Payment for Order #12345')
        ->orderInvoiceNumber('INV_' . time())
        ->customerId('customer_001')
        ->successUrl('https://yoursite.com/payment/success')
        ->errorUrl('https://yoursite.com/payment/error')
        ->cancelUrl('https://yoursite.com/payment/cancel')
        ->build();

    // Generate form fields with signature
    $formFields = $sepay->checkout()->generateFormFields($checkoutData);

    echo "Generated form fields:\n";
    foreach ($formFields as $key => $value) {
        echo "  {$key}: {$value}\n";
    }

    echo "\nCheckout URL: " . $sepay->checkout()->getCheckoutUrl('sandbox') . "\n\n";

    // Generate complete HTML form
    echo "=== HTML Form ===\n";
    $htmlForm = $sepay->checkout()->generateFormHtml(
        $checkoutData,
        'sandbox',
        [
            'id' => 'sepay-payment-form',
            'class' => 'payment-form',
            'style' => 'margin: 20px; padding: 20px; border: 1px solid #ccc;'
        ]
    );

    echo $htmlForm . "\n\n";

    echo "=== Usage Instructions ===\n";
    echo "1. Copy the HTML form above to your website\n";
    echo "2. When user clicks submit, they'll be redirected to SePay\n";
    echo "3. After payment, user will be redirected to your success/error/cancel URL\n";
    echo "4. Use the Orders API to check payment status\n\n";

    echo "Basic checkout example completed successfully!\n";
} catch (SePayException $e) {
    echo "SePay Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
