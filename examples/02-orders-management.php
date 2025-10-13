<?php

/**
 * Example 3: Orders Management
 *
 * Shows how to manage orders after they're created through checkout.
 * Covers listing, retrieving, and voiding orders.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SePay\SePayClient;
use SePay\Exceptions\SePayException;

// Initialize SePay client
$sepay = new SePayClient(
    'SP-TEST-XXXXXXX',
    'spsk_live_xxxxxxxxxxxo99PoE7RsBpss3EFH5nV',
    SePayClient::ENVIRONMENT_SANDBOX
);

try {
    echo "=== Orders Management Example ===\n\n";

    // 1. List recent orders
    echo "1. Listing Recent Orders\n";
    echo "========================\n";

    $orders = $sepay->orders()->list([
        'order_status' => 'CAPTURED',  // Only successful payments
        'per_page' => 10,
        'from_created_at' => '2024-01-01',
        'to_created_at' => date('Y-m-d')
    ]);

    echo "Found " . count($orders['data']) . " orders:\n";
    foreach ($orders['data'] as $order) {
        echo "  • {$order['order_invoice_number']}: {$order['order_amount']} {$order['order_currency']} - {$order['order_status']}\n";
    }
    echo "\n";

    // 2. Retrieve specific order
    if (!empty($orders['data'])) {
        echo "2. Retrieving Specific Order\n";
        echo "============================\n";

        $firstOrder = $orders['data'][0];
        $orderInvoiceNumber = $firstOrder['order_invoice_number'];

        echo "Retrieving order: {$orderInvoiceNumber}\n";
        $orderDetails = $sepay->orders()->retrieve($orderInvoiceNumber);

        echo "Order Details:\n";
        echo "  ID: {$orderDetails['data']['id']}\n";
        echo "  Invoice: {$orderDetails['data']['order_invoice_number']}\n";
        echo "  Status: {$orderDetails['data']['order_status']}\n";
        echo "  Amount: {$orderDetails['data']['order_amount']} {$orderDetails['data']['order_currency']}\n";
        echo "  Description: {$orderDetails['data']['order_description']}\n";
        echo "  Created: {$orderDetails['data']['created_at']}\n";

        // Show transactions
        if (!empty($orderDetails['data']['transactions'])) {
            echo "  Transactions:\n";
            foreach ($orderDetails['data']['transactions'] as $transaction) {
                echo "    - {$transaction['transaction_type']}: {$transaction['transaction_amount']} {$transaction['transaction_currency']}\n";
                echo "      Status: {$transaction['transaction_status']}\n";
                echo "      Method: {$transaction['payment_method']}\n";
                if (isset($transaction['card_number'])) {
                    echo "      Card: {$transaction['card_number']} ({$transaction['card_brand']})\n";
                }
                echo "      Date: {$transaction['transaction_date']}\n";
            }
        }
        echo "\n";
    }

    // 3. List orders with different filters
    echo "3. Advanced Order Filtering\n";
    echo "===========================\n";

    // All orders (any status)
    $allOrders = $sepay->orders()->list(['per_page' => 5]);
    echo "All orders (last 5): " . count($allOrders['data']) . " found\n";

    // Orders from specific date range
    $recentOrders = $sepay->orders()->list([
        'from_created_at' => date('Y-m-d', strtotime('-7 days')),
        'to_created_at' => date('Y-m-d'),
        'per_page' => 20
    ]);
    echo "Orders from last 7 days: " . count($recentOrders['data']) . " found\n";

    // Failed orders
    $failedOrders = $sepay->orders()->list([
        'order_status' => 'FAILED',
        'per_page' => 10
    ]);
    echo "Failed orders: " . count($failedOrders['data']) . " found\n\n";

    // 4. Void transaction example (commented out to avoid affecting real data)
    echo "4. Void Transaction Example\n";
    echo "===========================\n";
    echo "// To void a transaction (cancel/refund):\n";
    echo "// \$result = \$sepay->orders()->voidTransaction('ORDER_INVOICE_NUMBER');\n";
    echo "// \n";
    echo "// Example:\n";
    if (!empty($orders['data'])) {
        $exampleInvoice = $orders['data'][0]['order_invoice_number'];
        echo "// \$result = \$sepay->orders()->voidTransaction('{$exampleInvoice}');\n";
        echo "// if (\$result['success']) {\n";
        echo "//     echo 'Transaction voided successfully';\n";
        echo "// }\n";
    }
    echo "\n";

    echo "=== Order Status Reference ===\n";
    echo "• PENDING - Payment initiated but not completed\n";
    echo "• CAPTURED - Payment successful\n";
    echo "• FAILED - Payment failed\n";
    echo "• VOIDED - Payment cancelled/refunded\n\n";

    echo "=== Usage Tips ===\n";
    echo "• Use order_invoice_number (not ID) to retrieve orders\n";
    echo "• Filter by order_status to get specific types of orders\n";
    echo "• Use date ranges to limit results for better performance\n";
    echo "• Check transaction details for payment method information\n";
    echo "• Void transactions only when necessary (irreversible)\n\n";

    echo "✅ Orders management examples completed successfully!\n";
} catch (SePayException $e) {
    echo "SePay Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";

    if (method_exists($e, 'getErrorDetails') && $e->getErrorDetails()) {
        echo "Error Details: " . json_encode($e->getErrorDetails(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
