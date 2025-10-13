# SePay PHP SDK Examples

This directory has examples showing how to use the SePay PHP SDK.

## Quick Start

Run the overview example first:

```bash
php examples/basic-usage.php
```

## Example Files

### 1. basic-usage.php - Quick Overview

- Overview of SDK features
- Basic connectivity test
- Links to other examples

### 2. 01-checkout-basic.php - Basic Checkout

- Simple one-time payment
- Form field generation
- HTML form creation
- Most common use case

### 3. 02-orders-management.php - Orders Management

- List orders with filters
- Get order details
- Void transactions
- Order status checking

### 4. 03-payment-methods.php - Payment Methods

- Card payments
- Bank transfer payments
- Payment method selection
- Payment method comparison

## Usage Recommendations

### For New Users

1. Start with `basic-usage.php` for overview
2. Run `01-checkout-basic.php` for your first payment
3. Check other examples as needed

### For E-commerce

1. `01-checkout-basic.php` - Product purchases
2. `03-payment-methods.php` - Payment options
3. `02-orders-management.php` - Order tracking

## Configuration

To use your own credentials, update the client initialization:

```php
$sepay = new SePayClient(
    'YOUR_MERCHANT_ID',
    'YOUR_SECRET_KEY',
    SePayClient::ENVIRONMENT_PRODUCTION  // For live environment
);
```

## Example Output

Each example provides:

- Clear explanations of what's happening
- Code comments explaining each step
- Practical tips for implementation
- Important notes and best practices
- Real data from your SePay account

## Testing

### Prerequisites

```bash
# Install dependencies
composer install

# Ensure you have valid SePay credentials
```

### Running Examples

```bash
# Quick overview
php examples/basic-usage.php

# Specific features
php examples/01-checkout-basic.php
php examples/02-orders-management.php
php examples/03-payment-methods.php
```

### Expected Results

- All examples should run without errors
- Real data from your SePay sandbox account
- Working checkout URLs and form fields
- Detailed information about orders

## Troubleshooting

### Common Issues

**Authentication Error**

```
SePay Error: Authentication failed
```

- Check your merchant ID and secret key
- Ensure you're using the correct environment

**No Data Found**

```
Found 0 orders
```

- Normal for new accounts
- Create test payments through checkout examples first

**Network Error**

```
Connection timeout
```

- Check internet connection
- Verify SePay service status

### Getting Help

1. Check the main README.md for SDK documentation
2. Review example code for implementation details
3. Test in sandbox before going to production
4. Contact SePay support for account-specific issues

## Next Steps

After running the examples:

1. Integrate checkout into your application
2. Set up webhooks (if supported by your SePay plan)
3. Implement order tracking for your customers
4. Test thoroughly in sandbox environment
5. Deploy to production with your live credentials

## Success

If all examples run successfully, you're ready to integrate SePay into your application!

Remember to:

- Use production credentials for live environment
- Implement proper error handling
- Test all payment flows thoroughly
- Monitor transactions regularly
