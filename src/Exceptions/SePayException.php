<?php

declare(strict_types=1);

namespace SePay\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception class for all SePay SDK exceptions
 *
 * @package SePay\Exceptions
 */
class SePayException extends Exception
{
    /** @var array<string, mixed> */
    protected array $context = [];

    protected ?string $errorCode = null;

    /** @var array<string, mixed>|null */
    protected ?array $errorDetails = null;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get additional context information
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set error code from API response
     */
    public function setErrorCode(?string $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * Get error code from API response
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Set error details from API response
     *
     * @param array<string, mixed>|null $errorDetails
     */
    public function setErrorDetails(?array $errorDetails): self
    {
        $this->errorDetails = $errorDetails;
        return $this;
    }

    /**
     * Get error details from API response
     *
     * @return array<string, mixed>|null
     */
    public function getErrorDetails(): ?array
    {
        return $this->errorDetails;
    }

    /**
     * Convert exception to array for logging/debugging
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'error_code' => $this->getErrorCode(),
            'error_details' => $this->getErrorDetails(),
            'context' => $this->getContext(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
}
