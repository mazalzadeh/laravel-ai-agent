<?php

namespace App\AI\Tools;

use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;

/**
 * Represent a tool that retrieves order status information for a customer.
 */
class GetOrderStatusTool implements ToolInterface
{
    /**
     * Return the unique identifier name of the tool.
     *
     * @return string The unique tool identifier.
     */
    public function name(): string
    {
        return 'get_order_status';
    }


    /**
     * Return a natural language description explaining what the tool does.
     *
     * @return string The description of the tool.
     */
    public function description(): string
    {
        return 'Retrieve the delivery and payment status of a customer order using the order ID.';
    }


    /**
     * Return the JSON schema definition for the tool's input parameters.
     *
     * @return array The parameters schema as an associative array.
     */
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'order_id' => [
                    'type' => 'string',
                    'description' => 'The unique identifier of the order, e.g. ORD-12345',
                ],
            ],
            'required' => ['order_id'],
            'additionalProperties' => false,
        ];
    }


    /**
     * Execute the tool logic to fetch details of a specific order.
     *
     * @param array $arguments The parameters containing the order_id.
     * @return array The order status details as an associative array.
     *
     * @throws \InvalidArgumentException If the required order_id is missing or empty.
     */
    public function execute(array $arguments): array
    {
        $validated = $this->validateArguments($arguments);

        //Simulating a database response or order service
        return [
            'order_id' => $validated['order_id'],
            'status' => 'shipped',
            'carrier' => 'Post Iran',
            'tracking_number' => 'IR9876543210',
            'estimated_delivery' => '2026-08-02',
            'payment_status' => 'paid',
        ];
    }


    private function validateArguments(array $arguments):array
    {
        $validated=Validator::make(
            data:$arguments,
            rules:[
                'order_id'=>[
                    'required',
                    'string',
                    'regex:/^ORD-\d+$/'
                ],
            ],
            messages:[
                'order_id.required'=>'The order_id argument is required.',
                'order_id.string'=>'The order_id argument must be a string.',
                'order_id.regex'=>'The order_id must match the ORD-1234 format.'
            ],
        )->validate();

        return $validated;
    }
}
