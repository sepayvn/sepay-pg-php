<?php

declare(strict_types=1);

namespace SePay\Resources;

use SePay\Client\HttpClient;
use SePay\Exceptions\ValidationException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Base resource class providing common functionality for all API resources
 *
 * @package SePay\Resources
 */
abstract class BaseResource
{
    protected HttpClient $httpClient;

    protected LoggerInterface $logger;

    public function __construct(HttpClient $httpClient, ?LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Validate required fields
     *
     * @param array<string, mixed> $data
     * @param array<string> $requiredFields
     */
    protected function validateRequired(array $data, array $requiredFields): void
    {
        $missing = [];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || (is_array($data[$field]) && empty($data[$field]))) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw new ValidationException(
                'Missing required fields: ' . implode(', ', $missing),
                400,
                null,
                ['missing_fields' => $missing],
            );
        }
    }

    /**
     * Validate field values against rules
     *
     * @param array<string, mixed> $data
     * @param array<string, array<string>> $rules
     */
    protected function validateFields(array $data, array $rules): void
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            if (! isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            foreach ($fieldRules as $rule) {
                $error = $this->validateFieldRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }

        if (! empty($errors)) {
            $exception = new ValidationException('Validation failed', 400);
            $exception->setValidationErrors($errors);

            throw $exception;
        }
    }

    /**
     * Validate a single field rule
     *
     * @param mixed $value
     */
    private function validateFieldRule(string $field, $value, string $rule): ?string
    {
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $ruleValue = $parts[1] ?? null;

        switch ($ruleName) {
            case 'required':
                return empty($value) ? "Field {$field} is required" : null;
            case 'string':
                return !is_string($value) ? "Field {$field} must be a string" : null;
            case 'integer':
                return !is_int($value) && !ctype_digit((string) $value) ? "Field {$field} must be an integer" : null;
            case 'numeric':
                return !is_numeric($value) ? "Field {$field} must be numeric" : null;
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "Field {$field} must be a valid email" : null;
            case 'url':
                return !filter_var($value, FILTER_VALIDATE_URL) ? "Field {$field} must be a valid URL" : null;
            case 'min':
                return strlen((string) $value) < (int) $ruleValue ? "Field {$field} must be at least {$ruleValue} characters" : null;
            case 'max':
                return strlen((string) $value) > (int) $ruleValue ? "Field {$field} must not exceed {$ruleValue} characters" : null;
            case 'in':
                return ! in_array($value, explode(',', $ruleValue), true) ? "Field {$field} must be one of: {$ruleValue}" : null;
            default:
                return null;
        }
    }

    /**
     * Filter data to only include allowed fields
     *
     * @param array<string, mixed> $data
     * @param array<string> $allowedFields
     * @return array<string, mixed>
     */
    protected function filterFields(array $data, array $allowedFields): array
    {
        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Convert array to query string
     *
     * @param array<string, mixed> $params
     */
    protected function buildQueryString(array $params): string
    {
        $filtered = array_filter($params, fn($value) => $value !== null && $value !== '');

        return http_build_query($filtered);
    }

    /**
     * Log API operation
     *
     * @param array<string, mixed> $context
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        $this->logger->info("SePay API: {$operation}", $context);
    }

    /**
     * Get resource endpoint
     */
    abstract protected function getResourceEndpoint(): string;
}
