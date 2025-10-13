<?php

declare(strict_types=1);

namespace SePay\Auth;

/**
 * Signature generator for SePay checkout forms
 *
 * Implements HMAC-SHA256 signature generation for checkout form fields.
 * Note: API authentication uses Basic Auth (merchant_id:secret_key)
 *
 * @package SePay\Auth
 */
class SignatureGenerator
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function signCheckoutFields(array $fields): string
    {
        $signed = [];

        $signedFields = array_values(array_filter(array_keys($fields), fn($field) => in_array($field, [
            'merchant',
            'env',
            'operation',
            'payment_method',
            'order_amount',
            'currency',
            'order_invoice_number',
            'order_description',
            'customer_id',
            'agreement_id',
            'agreement_name',
            'agreement_type',
            'agreement_payment_frequency',
            'agreement_amount_per_payment',
            'success_url',
            'error_url',
            'cancel_url',
        ])));

        foreach ($signedFields as $field) {
            if (! isset($fields[$field])) {
                continue;
            }

            $signed[] = $field . "=" . ($fields[$field] ?? '');
        }

        return base64_encode(hash_hmac('sha256', implode(',', $signed), $this->secretKey, true));
    }
}
