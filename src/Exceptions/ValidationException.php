<?php

declare(strict_types=1);

namespace SePay\Exceptions;

/**
 * Exception thrown when request validation fails
 *
 * This includes missing required fields, invalid field values, or format errors
 *
 * @package SePay\Exceptions
 */
class ValidationException extends SePayException
{
    /** @var array<string, mixed> */
    private array $validationErrors = [];

    /**
     * Set validation errors from API response
     *
     * @param array<string, mixed> $errors
     */
    public function setValidationErrors(array $errors): self
    {
        $this->validationErrors = $errors;
        return $this;
    }

    /**
     * Get validation errors
     *
     * @return array<string, mixed>
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Check if a specific field has validation errors
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->validationErrors[$field]);
    }

    /**
     * Get validation errors for a specific field
     *
     * @return array<mixed>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->validationErrors[$field] ?? [];
    }
}
