<?php

declare(strict_types=1);

namespace SePay\Builders;

use SePay\Exceptions\ValidationException;

/**
 * Fluent builder for creating checkout form fields
 *
 * @package SePay\Builders
 */
class CheckoutBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Create a new checkout builder instance
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Set merchant ID
     */
    public function merchant(string $merchant): self
    {
        if (empty($merchant)) {
            throw new ValidationException('Merchant ID is required');
        }

        $this->data['merchant'] = $merchant;

        return $this;
    }

    /**
     * Set payment method
     */
    public function paymentMethod(string $paymentMethod): self
    {
        $allowedMethods = ['CARD', 'BANK_TRANSFER', 'NAPAS_BANK_TRANSFER'];

        if (! in_array($paymentMethod, $allowedMethods, true)) {
            throw new ValidationException(
                'Payment method must be one of: ' . implode(', ', $allowedMethods),
            );
        }

        $this->data['payment_method'] = $paymentMethod;

        return $this;
    }

    /**
     * Set order amount in smallest currency unit (e.g., dong for VND)
     */
    public function orderAmount(int $amount): self
    {
        if ($amount < 0) {
            throw new ValidationException('Order amount must be greater than or equal to 0');
        }

        $this->data['order_amount'] = $amount;

        return $this;
    }

    /**
     * Set currency code
     */
    public function currency(string $currency): self
    {
        $allowedCurrencies = ['VND'];

        if (! in_array($currency, $allowedCurrencies, true)) {
            throw new ValidationException(
                'Currency must be one of: ' . implode(', ', $allowedCurrencies),
            );
        }

        $this->data['currency'] = $currency;

        return $this;
    }

    /**
     * Set order invoice number
     */
    public function orderInvoiceNumber(string $invoiceNumber): self
    {
        if (empty($invoiceNumber)) {
            throw new ValidationException('Order invoice number cannot be empty');
        }

        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $invoiceNumber)) {
            throw new ValidationException('Order invoice number can only contain letters, numbers, underscores, and hyphens');
        }

        if (strlen($invoiceNumber) > 100) {
            throw new ValidationException('Order invoice number cannot exceed 100 characters');
        }

        $this->data['order_invoice_number'] = $invoiceNumber;

        return $this;
    }

    /**
     * Set operation type
     */
    public function operation(string $operation): self
    {
        $allowedOperations = ['PURCHASE', 'VERIFY'];

        if (! in_array($operation, $allowedOperations, true)) {
            throw new ValidationException(
                'Operation must be one of: ' . implode(', ', $allowedOperations),
            );
        }

        $this->data['operation'] = $operation;

        return $this;
    }

    /**
     * Set order description
     */
    public function orderDescription(string $description): self
    {
        if (empty($description)) {
            throw new ValidationException('Order description is required');
        }

        $this->data['order_description'] = $description;

        return $this;
    }

    /**
     * Set customer ID
     */
    public function customerId(string $customerId): self
    {
        $this->data['customer_id'] = $customerId;

        return $this;
    }

    /**
     * Set success URL
     */
    public function successUrl(string $url): self
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Success URL must be a valid URL');
        }

        $this->data['success_url'] = $url;

        return $this;
    }

    /**
     * Set error URL
     */
    public function errorUrl(string $url): self
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Error URL must be a valid URL');
        }

        $this->data['error_url'] = $url;

        return $this;
    }

    /**
     * Set cancel URL
     */
    public function cancelUrl(string $url): self
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ValidationException('Cancel URL must be a valid URL');
        }

        $this->data['cancel_url'] = $url;

        return $this;
    }

    /**
     * Set branch code (for bank transfer)
     */
    public function branchCode(string $branchCode): self
    {
        $this->data['branch_code'] = $branchCode;

        return $this;
    }

    /**
     * Build and return the checkout data array
     *
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $this->validateRequiredFields();
        $this->validateBusinessRules();

        return $this->data;
    }

    /**
     * Validate required fields
     */
    private function validateRequiredFields(): void
    {
        $requiredFields = ['currency', 'order_amount', 'operation', 'order_description'];

        foreach ($requiredFields as $field) {
            if (! isset($this->data[$field])) {
                throw new ValidationException("Required field '{$field}' is missing");
            }
        }
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules(): void
    {
        if ($this->data['operation'] === 'PURCHASE' && ! isset($this->data['order_invoice_number'])) {
            throw new ValidationException('Order invoice number is required for PURCHASE operation');
        }

        if ($this->data['operation'] === 'PURCHASE' && $this->data['order_amount'] <= 0) {
            throw new ValidationException('Order amount must be greater than 0 for PURCHASE operation');
        }

        if ($this->data['operation'] === 'VERIFY' && $this->data['order_amount'] > 0) {
            throw new ValidationException('Order amount must be 0 for VERIFY operation');
        }
    }
}
