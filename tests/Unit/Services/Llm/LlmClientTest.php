<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\Contracts\LlmClient as LlmClientContract;
use App\Services\Llm\LlmClient;
use PHPUnit\Framework\TestCase;

class LlmClientTest extends TestCase
{
    public function test_it_implements_llm_client_contract(): void
    {
        $client = new LlmClient();

        $this->assertInstanceOf(LlmClientContract::class, $client);
    }


    public function test_it_returns_usage_from_request_completion_response(): void
    {
        $client = new LlmClient();

        $response = $client->requestCompletion('وضعیت سفارش ORD-2024 چیست؟');

        $this->assertArrayHasKey('usage', $response);
        $this->assertSame(10, $response['usage']['prompt_tokens']);
        $this->assertSame(20, $response['usage']['completion_tokens']);
        $this->assertSame(30, $response['usage']['total_tokens']);
    }


    public function test_it_returns_usage_from_final_tool_response(): void
    {
        $client = new LlmClient();

        $response = $client->chat([
            [
                'role' => 'tool',
                'content' => json_encode([
                    'order_id' => 'ORD-2024',
                    'status' => 'shipped',
                    'carrier' => 'DHL',
                    'tracking_number' => 'TRACK-123',
                    'estimated_delivery' => '2026-08-12',
                    'payment_status' => 'paid',
                ]),
            ],
        ]);

        $this->assertArrayHasKey('usage', $response);
        $this->assertSame(10, $response['usage']['prompt_tokens']);
        $this->assertSame(20, $response['usage']['completion_tokens']);
        $this->assertSame(30, $response['usage']['total_tokens']);
    }
}
