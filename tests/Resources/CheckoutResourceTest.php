<?php

declare(strict_types=1);

namespace SePay\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Mockery;
use SePay\Resources\CheckoutResource;
use SePay\Auth\SignatureGenerator;
use SePay\Exceptions\ValidationException;
use Psr\Log\NullLogger;

class CheckoutResourceTest extends TestCase
{
    /** @var \Mockery\MockInterface|\SePay\Client\HttpClient */
    private $mockHttpClient;

    private CheckoutResource $checkoutResource;

    private SignatureGenerator $signatureGenerator;

    protected function setUp(): void
    {
        $this->mockHttpClient = Mockery::mock(\SePay\Client\HttpClient::class);
        $this->mockHttpClient->shouldReceive('getApiBaseUrl')->andReturn('https://test.sepay.dev');

        /** @phpstan-ignore-next-line */
        $this->checkoutResource = new CheckoutResource($this->mockHttpClient, new NullLogger());
        $this->signatureGenerator = new SignatureGenerator('test_secret_key');
        $this->checkoutResource->setSignatureGenerator($this->signatureGenerator);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGenerateFormFields(): void
    {
        $checkoutData = [
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
            'order_invoice_number' => 'INV_001',
            'customer_id' => 'customer_001',
        ];

        $formFields = $this->checkoutResource->generateFormFields($checkoutData);

        $this->assertEquals('TEST_MERCHANT', $formFields['merchant']);
        $this->assertEquals('VND', $formFields['currency']);
        $this->assertEquals('100000', $formFields['order_amount']);
        $this->assertEquals('PURCHASE', $formFields['operation']);
        $this->assertEquals('Test order', $formFields['order_description']);
        $this->assertEquals('INV_001', $formFields['order_invoice_number']);
        $this->assertEquals('customer_001', $formFields['customer_id']);
        $this->assertArrayHasKey('signature', $formFields);
        $this->assertNotEmpty($formFields['signature']);
    }

    public function testGenerateFormFieldsWithoutSignatureGenerator(): void
    {
        $checkoutResource = new CheckoutResource($this->mockHttpClient, new NullLogger());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Signature generator not initialized');

        $checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
        ]);
    }

    public function testValidationRequiredFields(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Required field 'merchant' is missing or empty");

        $this->checkoutResource->generateFormFields([
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
        ]);
    }

    public function testValidationInvalidCurrency(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Only VND currency is supported');

        $this->checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'USD',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
        ]);
    }

    public function testValidationInvalidOperation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Operation must be PURCHASE or VERIFY');

        $this->checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'INVALID',
            'order_description' => 'Test order',
        ]);
    }

    public function testValidationPurchaseRequiresInvoiceNumber(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order invoice number is required for PURCHASE operation');

        $this->checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
        ]);
    }

    public function testValidationPurchaseRequiresPositiveAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order amount must be greater than 0 for PURCHASE operation');

        $this->checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 0,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
            'order_invoice_number' => 'INV_001',
        ]);
    }

    public function testValidationVerifyRequiresZeroAmount(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Order amount must be 0 for VERIFY operation');

        $this->checkoutResource->generateFormFields([
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'VERIFY',
            'order_description' => 'Test order',
        ]);
    }

    public function testGetCheckoutUrlSandbox(): void
    {
        $url = $this->checkoutResource->getCheckoutUrl('sandbox');
        $expectedUrl = \SePay\Config\UrlConfig::getCheckoutUrl('sandbox');
        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetCheckoutUrlProduction(): void
    {
        $url = $this->checkoutResource->getCheckoutUrl('production');
        $expectedUrl = \SePay\Config\UrlConfig::getCheckoutUrl('production');
        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetCheckoutUrlWithCustomBaseUrl(): void
    {
        $customBaseUrl = 'https://custom-checkout.example.com';
        $this->checkoutResource->setCheckoutBaseUrl($customBaseUrl);

        $url = $this->checkoutResource->getCheckoutUrl('sandbox');
        $expectedUrl = $customBaseUrl . '/v1/checkout/init';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testGetCheckoutUrlWithCustomBaseUrlTrimsSlash(): void
    {
        $customBaseUrl = 'https://custom-checkout.example.com/';
        $this->checkoutResource->setCheckoutBaseUrl($customBaseUrl);

        $url = $this->checkoutResource->getCheckoutUrl('sandbox');
        $expectedUrl = 'https://custom-checkout.example.com/v1/checkout/init';
        $this->assertEquals($expectedUrl, $url);
    }

    public function testSetCheckoutBaseUrl(): void
    {
        $customBaseUrl = 'https://my-checkout.example.com';
        $this->checkoutResource->setCheckoutBaseUrl($customBaseUrl);

        // Test that the custom URL is used
        $url = $this->checkoutResource->getCheckoutUrl('sandbox');
        $this->assertStringStartsWith($customBaseUrl, $url);
        $this->assertStringEndsWith('/v1/checkout/init', $url);
    }

    public function testVerifySignature(): void
    {
        $fields = [
            'merchant' => 'TEST_MERCHANT',
            'payment_method' => 'CARD',
            'order_amount' => '100000',
            'currency' => 'VND',
            'order_invoice_number' => 'INV_001',
            'customer_id' => 'customer_001',
        ];

        $signature = $this->signatureGenerator->signCheckoutFields($fields);
        $isValid = $this->checkoutResource->verifySignature($fields, $signature);

        $this->assertTrue($isValid);
    }

    public function testVerifyInvalidSignature(): void
    {
        $fields = [
            'merchant' => 'TEST_MERCHANT',
            'payment_method' => 'CARD',
            'order_amount' => '100000',
            'currency' => 'VND',
            'order_invoice_number' => 'INV_001',
            'customer_id' => 'customer_001',
        ];

        $isValid = $this->checkoutResource->verifySignature($fields, 'invalid_signature');

        $this->assertFalse($isValid);
    }

    public function testGenerateFormHtml(): void
    {
        $checkoutData = [
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
            'order_invoice_number' => 'INV_001',
        ];

        $html = $this->checkoutResource->generateFormHtml($checkoutData, 'sandbox', [
            'id' => 'test-form',
            'class' => 'checkout-form',
        ]);

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="POST"', $html);
        $expectedUrl = \SePay\Config\UrlConfig::getCheckoutUrl('sandbox');
        $this->assertStringContainsString('action="' . $expectedUrl . '"', $html);
        $this->assertStringContainsString('id="test-form"', $html);
        $this->assertStringContainsString('class="checkout-form"', $html);
        $this->assertStringContainsString('name="merchant"', $html);
        $this->assertStringContainsString('value="TEST_MERCHANT"', $html);
        $this->assertStringContainsString('name="signature"', $html);
        $this->assertStringContainsString('<button type="submit">Proceed to Payment</button>', $html);
        $this->assertStringContainsString('</form>', $html);
    }

    public function testGenerateFormHtmlWithCustomCheckoutUrl(): void
    {
        $customBaseUrl = 'https://custom-checkout.example.com';
        $this->checkoutResource->setCheckoutBaseUrl($customBaseUrl);

        $checkoutData = [
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
            'order_invoice_number' => 'INV_001',
        ];

        $html = $this->checkoutResource->generateFormHtml($checkoutData, 'sandbox', [
            'id' => 'custom-form',
            'class' => 'custom-checkout-form',
        ]);

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="POST"', $html);
        $expectedUrl = $customBaseUrl . '/v1/checkout/init';
        $this->assertStringContainsString('action="' . $expectedUrl . '"', $html);
        $this->assertStringContainsString('id="custom-form"', $html);
        $this->assertStringContainsString('class="custom-checkout-form"', $html);
    }

    public function testGenerateFormHtmlWithoutSubmitButton(): void
    {
        $checkoutData = [
            'merchant' => 'TEST_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test order',
            'order_invoice_number' => 'INV_001',
        ];

        $html = $this->checkoutResource->generateFormHtml($checkoutData, 'sandbox', [
            'no_submit_button' => true,
        ]);

        $this->assertStringNotContainsString('<button type="submit">', $html);
    }

    public function testGenerateAutoSubmitScript(): void
    {
        $script = $this->checkoutResource->generateAutoSubmitScript('my-form');

        $this->assertEquals('<script>document.getElementById("my-form").submit();</script>', $script);
    }

    public function testGenerateAutoSubmitScriptDefaultId(): void
    {
        $script = $this->checkoutResource->generateAutoSubmitScript();

        $this->assertEquals('<script>document.getElementById("sepay-checkout-form").submit();</script>', $script);
    }

    public function testAutoSetMerchantFromClient(): void
    {
        $this->checkoutResource->setMerchantId('AUTO_MERCHANT');

        $checkoutData = [
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test auto merchant',
            'order_invoice_number' => 'AUTO_001',
        ];

        $formFields = $this->checkoutResource->generateFormFields($checkoutData);

        $this->assertEquals('AUTO_MERCHANT', $formFields['merchant']);
        $this->assertArrayHasKey('signature', $formFields);
    }

    public function testOverrideMerchantWhenProvided(): void
    {
        $this->checkoutResource->setMerchantId('AUTO_MERCHANT');

        $checkoutData = [
            'merchant' => 'OVERRIDE_MERCHANT',
            'currency' => 'VND',
            'order_amount' => 100000,
            'operation' => 'PURCHASE',
            'order_description' => 'Test override merchant',
            'order_invoice_number' => 'OVERRIDE_001',
        ];

        $formFields = $this->checkoutResource->generateFormFields($checkoutData);

        $this->assertEquals('OVERRIDE_MERCHANT', $formFields['merchant']);
        $this->assertArrayHasKey('signature', $formFields);
    }
}
