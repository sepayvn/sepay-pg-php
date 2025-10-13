<?php

declare(strict_types=1);

namespace SePay\Resources;

use SePay\Exceptions\ValidationException;

/**
 * Order resource for managing payment orders
 *
 * @package SePay\Resources
 */
class OrderResource extends BaseResource
{
    protected function getResourceEndpoint(): string
    {
        return 'order';
    }

    /**
     * Retrieve an order by ID
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $orderId): array
    {
        if (empty($orderId)) {
            throw new ValidationException('Order ID is required');
        }

        $this->logOperation('Retrieve Order', ['order_id' => $orderId]);

        return $this->httpClient->get("{$this->getResourceEndpoint()}/detail/{$orderId}");
    }

    /**
     * List orders with optional filters
     *
     * @param array{
     *     per_page?: int,
     *     q?: string,
     *     customer_id?: string,
     *     order_status?: string,
     *     created_at?: string,
     *     from_created_at?: string,
     *     to_created_at?: string,
     *     sort?: array{created_at?: string}
     * } $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        $allowedFilters = [
            'per_page',
            'q',
            'customer_id',
            'order_status',
            'created_at',
            'from_created_at',
            'to_created_at',
            'sort',
        ];

        $filteredParams = $this->filterFields($filters, $allowedFilters);

        $this->logOperation('List Orders', ['filters' => $filteredParams]);

        return $this->httpClient->get($this->getResourceEndpoint(), $filteredParams);
    }

    /**
     * Void a transaction for an order
     *
     * @return array<string, mixed>
     */
    public function voidTransaction(string $orderInvoiceNumber): array
    {
        if (empty($orderInvoiceNumber)) {
            throw new ValidationException('Order invoice number is required');
        }

        $this->logOperation('Void Transaction', ['order_invoice_number' => $orderInvoiceNumber]);

        return $this->httpClient->post($this->getResourceEndpoint() . '/voidTransaction', [
            'order_invoice_number' => $orderInvoiceNumber,
        ]);
    }

    /**
     * Cancel an order
     *
     * @return array<string, mixed>
     */
    public function cancel(string $orderInvoiceNumber): array
    {
        if (empty($orderInvoiceNumber)) {
            throw new ValidationException('Order invoice number is required');
        }

        $this->logOperation('Cancel Order', ['order_invoice_number' => $orderInvoiceNumber]);

        return $this->httpClient->post($this->getResourceEndpoint() . '/cancel', [
            'order_invoice_number' => $orderInvoiceNumber,
        ]);
    }
}
