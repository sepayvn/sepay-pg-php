<?php

declare(strict_types=1);

namespace SePay\Tests\Builders;

use PHPUnit\Framework\TestCase;
use SePay\Builders\CheckoutBuilder;
use SePay\Exceptions\ValidationException;

class CheckoutBuilderTest extends TestCase
{
    public function testMakeReturnsNewInstance(): void
    {
        $builder = CheckoutBuilder::make();
        $this->assertInstanceOf(CheckoutBuilder::class, $builder);
    }

    public function testBasicCheckoutBuild(): void
    {
        $data = CheckoutBuilder::make()
            ->merchant('TEST_MERCHANT')
            ->currency('VND')
            ->orderAmount(100000)
            ->operation('PURCHASE')
            ->orderDescription('Test order')
            ->orderInvoiceNumber('INV_001')
            ->build();

        $this->assertEquals('TEST_MERCHANT', $data['merchant']);
        $this->assertEquals('VND', $data['currency']);
        $this->assertEquals(100000, $data['order_amount']);
        $this->assertEquals('PURCHASE', $data['operation']);
        $this->assertEquals('Test order', $data['order_description']);
        $this->assertEquals('INV_001', $data['order_invoice_number']);
    }

    public function testPaymentMethodValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Payment method must be one of: CARD, BANK_TRANSFER');

        CheckoutBuilder::make()->paymentMethod('INVALID_METHOD');
    }

    public function testCurrencyValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Currency must be one of: VND');

        CheckoutBuilder::make()->currency('USD');
    }

    public function testOrderAmountValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order amount must be greater than or equal to 0');

        CheckoutBuilder::make()->orderAmount(-1);
    }

    public function testOrderInvoiceNumberValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order invoice number can only contain letters, numbers, underscores, and hyphens');

        CheckoutBuilder::make()->orderInvoiceNumber('INV@001');
    }

    public function testOrderInvoiceNumberLengthValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order invoice number cannot exceed 100 characters');

        CheckoutBuilder::make()->orderInvoiceNumber(str_repeat('A', 101));
    }

    public function testOperationValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Operation must be one of: PURCHASE, VERIFY');

        CheckoutBuilder::make()->operation('INVALID_OPERATION');
    }

    public function testUrlValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Success URL must be a valid URL');

        CheckoutBuilder::make()->successUrl('invalid-url');
    }

    public function testRequiredFieldsValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Required field 'currency' is missing");

        CheckoutBuilder::make()->build();
    }

    public function testPurchaseOperationRequiresInvoiceNumber(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order invoice number is required for PURCHASE operation');

        CheckoutBuilder::make()
            ->merchant('TEST_MERCHANT')
            ->currency('VND')
            ->orderAmount(100000)
            ->operation('PURCHASE')
            ->orderDescription('Test order')
            ->build();
    }

    public function testPurchaseOperationRequiresPositiveAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order amount must be greater than 0 for PURCHASE operation');

        CheckoutBuilder::make()
            ->merchant('TEST_MERCHANT')
            ->currency('VND')
            ->orderAmount(0)
            ->operation('PURCHASE')
            ->orderDescription('Test order')
            ->orderInvoiceNumber('INV_001')
            ->build();
    }

    public function testVerifyOperationRequiresZeroAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order amount must be 0 for VERIFY operation');

        CheckoutBuilder::make()
            ->merchant('TEST_MERCHANT')
            ->currency('VND')
            ->orderAmount(100000)
            ->operation('VERIFY')
            ->orderDescription('Test order')
            ->build();
    }

    public function testBankTransferPaymentBuild(): void
    {
        $data = CheckoutBuilder::make()
            ->merchant('TEST_MERCHANT')
            ->paymentMethod('BANK_TRANSFER')
            ->currency('VND')
            ->orderAmount(150000)
            ->operation('PURCHASE')
            ->orderDescription('Bank transfer payment')
            ->orderInvoiceNumber('BANK_001')
            ->customerId('customer_002')
            ->branchCode('001')
            ->successUrl('https://example.com/success')
            ->build();

        $this->assertEquals('TEST_MERCHANT', $data['merchant']);
        $this->assertEquals('BANK_TRANSFER', $data['payment_method']);
        $this->assertEquals('VND', $data['currency']);
        $this->assertEquals(150000, $data['order_amount']);
        $this->assertEquals('PURCHASE', $data['operation']);
        $this->assertEquals('Bank transfer payment', $data['order_description']);
        $this->assertEquals('BANK_001', $data['order_invoice_number']);
        $this->assertEquals('customer_002', $data['customer_id']);
        $this->assertEquals('001', $data['branch_code']);
        $this->assertEquals('https://example.com/success', $data['success_url']);
    }

    public function testEmptyMerchantValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Merchant ID is required');

        CheckoutBuilder::make()->merchant('');
    }

    public function testEmptyOrderDescriptionValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order description is required');

        CheckoutBuilder::make()->orderDescription('');
    }

    public function testEmptyOrderInvoiceNumberValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order invoice number cannot be empty');

        CheckoutBuilder::make()->orderInvoiceNumber('');
    }
}
