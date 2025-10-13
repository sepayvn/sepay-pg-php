<?php

declare(strict_types=1);

namespace SePay\Exceptions;

/**
 * Exception thrown when API rate limit is exceeded
 *
 * @package SePay\Exceptions
 */
class RateLimitException extends SePayException
{
    private ?int $retryAfter = null;

    /**
     * Set retry after seconds from API response
     */
    public function setRetryAfter(?int $retryAfter): self
    {
        $this->retryAfter = $retryAfter;
        return $this;
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
