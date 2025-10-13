<?php

declare(strict_types=1);

namespace SePay\Resources;

use SePay\Auth\SignatureGenerator;
use SePay\Exceptions\ValidationException;
use SePay\Config\UrlConfig;

/**
 * Checkout resource for generating checkout form fields
 *
 * @package SePay\Resources
 */
class CheckoutResource extends BaseResource
{
    private ?SignatureGenerator $signatureGenerator = null;

    private ?string $merchantId = null;

    private ?string $checkoutBaseUrl = null;

    /**
     * Set the signature generator with secret key
     */
    public function setSignatureGenerator(SignatureGenerator $signatureGenerator): void
    {
        $this->signatureGenerator = $signatureGenerator;
    }

    /**
     * Set the merchant ID from client
     */
    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    /**
     * Set the checkout base URL from client
     */
    public function setCheckoutBaseUrl(string $checkoutBaseUrl): void
    {
        $this->checkoutBaseUrl = rtrim($checkoutBaseUrl, '/');
    }

    protected function getResourceEndpoint(): string
    {
        return 'checkout';
    }

    /**
     * Generate checkout form fields with signature
     *
     * @param array<string, mixed> $checkoutData Checkout data from CheckoutBuilder
     * @return array<string, mixed> Form fields ready for submission to checkout endpoint
     */
    public function generateFormFields(array $checkoutData): array
    {
        if ($this->signatureGenerator === null) {
            throw new ValidationException('Signature generator not initialized. This should not happen.');
        }

        $this->logOperation('Generate Checkout Form Fields', $checkoutData);

        if (! isset($checkoutData['merchant']) && $this->merchantId !== null) {
            $checkoutData['merchant'] = $this->merchantId;
        }

        $this->validateCheckoutData($checkoutData);

        $formFields = $this->prepareFormFields($checkoutData);

        $formFields['signature'] = $this->signatureGenerator->signCheckoutFields($formFields);

        return $formFields;
    }

    /**
     * Get the checkout endpoint URL for form submission
     *
     * @param string $environment 'sandbox' or 'production'
     * @return string The checkout init endpoint URL
     */
    public function getCheckoutUrl(string $environment = 'sandbox'): string
    {
        if ($this->checkoutBaseUrl !== null) {
            return $this->checkoutBaseUrl . '/v1/checkout/init';
        }

        return UrlConfig::getCheckoutUrl($environment);
    }

    /**
     * Validate checkout data
     *
     * @param array<string, mixed> $data
     */
    private function validateCheckoutData(array $data): void
    {
        $requiredFields = ['currency', 'order_amount', 'operation', 'order_description'];

        if (! isset($data['merchant']) && $this->merchantId === null) {
            throw new ValidationException("Required field 'merchant' is missing or empty");
        }

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || (is_array($data[$field]) && empty($data[$field]))) {
                throw new ValidationException("Required field '{$field}' is missing or empty");
            }
        }

        if ($data['currency'] !== 'VND') {
            throw new ValidationException('Only VND currency is supported');
        }

        if (! in_array($data['operation'], ['PURCHASE', 'VERIFY'], true)) {
            throw new ValidationException('Operation must be PURCHASE or VERIFY');
        }

        if (isset($data['payment_method']) && ! in_array($data['payment_method'], ['CARD', 'BANK_TRANSFER', 'NAPAS_BANK_TRANSFER'], true)) {
            throw new ValidationException('Payment method must be one of: CARD, BANK_TRANSFER, NAPAS_BANK_TRANSFER');
        }

        if ($data['operation'] === 'PURCHASE') {
            if (! isset($data['order_invoice_number']) || empty($data['order_invoice_number'])) {
                throw new ValidationException('Order invoice number is required for PURCHASE operation');
            }

            if ($data['order_amount'] <= 0) {
                throw new ValidationException('Order amount must be greater than 0 for PURCHASE operation');
            }
        }

        if ($data['operation'] === 'VERIFY' && $data['order_amount'] > 0) {
            throw new ValidationException('Order amount must be 0 for VERIFY operation');
        }
    }

    /**
     * Prepare form fields for checkout
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareFormFields(array $data): array
    {
        $formFields = [];

        $formFields['merchant'] = $data['merchant'];
        $formFields['currency'] = $data['currency'];
        $formFields['order_amount'] = (string) $data['order_amount'];
        $formFields['operation'] = $data['operation'];
        $formFields['order_description'] = $data['order_description'];

        $optionalFields = [
            'payment_method',
            'order_invoice_number',
            'customer_id',
            'success_url',
            'error_url',
            'cancel_url',
            'branch_code',
            'agreement_id',
            'agreement_name',
            'agreement_type',
            'agreement_payment_frequency',
            'agreement_amount_per_payment',
        ];

        foreach ($optionalFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '' && (!is_array($data[$field]) || !empty($data[$field]))) {
                $formFields[$field] = (string) $data[$field];
            }
        }

        return $formFields;
    }

    /**
     * Verify checkout signature
     *
     * @param array<string, mixed> $fields Form fields including signature
     * @param string $signature The signature to verify
     * @return bool True if signature is valid
     */
    public function verifySignature(array $fields, string $signature): bool
    {
        if ($this->signatureGenerator === null) {
            throw new ValidationException('Signature generator not initialized. This should not happen.');
        }

        $expectedSignature = $this->signatureGenerator->signCheckoutFields($fields);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate a complete checkout form HTML
     *
     * @param array<string, mixed> $checkoutData Checkout data from CheckoutBuilder
     * @param string $environment 'sandbox' or 'production'
     * @param array<string, mixed> $formAttributes Additional form attributes
     * @return string HTML form ready for rendering
     */
    public function generateFormHtml(array $checkoutData, string $environment = 'sandbox', array $formAttributes = []): string
    {
        $formFields = $this->generateFormFields($checkoutData);
        $actionUrl = $this->getCheckoutUrl($environment);

        $defaultAttributes = [
            'method' => 'POST',
            'action' => $actionUrl,
        ];

        $attributes = array_merge($defaultAttributes, $formAttributes);

        $html = '<form';

        foreach ($attributes as $key => $value) {
            if ($key === 'no_submit_button') {
                continue;
            }

            $html .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars((string) $value) . '"';
        }

        $html .= '>' . PHP_EOL;

        foreach ($formFields as $name => $value) {
            $html .= '    <input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '">' . PHP_EOL;
        }

        if (! isset($formAttributes['no_submit_button']) || ! $formAttributes['no_submit_button']) {
            $html .= '    <button type="submit">Proceed to Payment</button>' . PHP_EOL;
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * Generate JavaScript for auto-submitting the form
     *
     * @param string $formId The ID of the form to submit
     * @return string JavaScript code for auto-submission
     */
    public function generateAutoSubmitScript(string $formId = 'sepay-checkout-form'): string
    {
        return sprintf(
            '<script>document.getElementById("%s").submit();</script>',
            htmlspecialchars($formId),
        );
    }
}
