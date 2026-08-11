<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Exceptions\LlmRateLimitExceededException;
use App\Services\Llm\Contracts\LlmClient as LlmClientContract;
use App\Services\Llm\LlmClient;
use App\Services\Llm\LlmRateLimiter;
use App\Services\Llm\MeteredLlmClient;
use App\Services\Llm\TokenUsageCalculator;
use App\Services\Llm\TokenUsageRecorder;
use App\Services\Llm\UsageProvider;
use Tests\TestCase;

class LlmUsageAndRateLimitTest extends TestCase
{
    public function test_it_returns_usage_for_a_successful_request_when_under_rate_limit(): void
    {
        $usageProvider = new class extends UsageProvider {
            public function getCurrentUsage(): int
            {
                return 0;
            }
            public function getRateLimit(): int
            {
                return 100;
            }
        };

        $rateLimiter = new LlmRateLimiter($usageProvider);
        $calculator = new TokenUsageCalculator();
        $client = new LlmClient();
        $recorder = new TokenUsageRecorder();

        $meteredClient = new MeteredLlmClient(
            $rateLimiter,
            $calculator,
            $client,
            $recorder,
        );

        $response = $meteredClient->requestCompletion('وضعیت سفارش ORD-2024 چیست؟');

        $this->assertArrayHasKey('choices', $response);
        $this->assertArrayHasKey('usage', $response);
        $this->assertSame(10, $response['usage']['prompt_tokens']);
        $this->assertSame(20, $response['usage']['completion_tokens']);
        $this->assertSame(30, $response['usage']['total_tokens']);
    }


    public function test_it_throws_when_rate_limit_is_exceeded(): void
    {
        $usageProvider = new class extends UsageProvider {
            public function getCurrentUsage(): int
            {
                return 101;
            }
            public function getRateLimit(): int
            {
                return 100;
            }
        };

        $rateLimiter = new LlmRateLimiter($usageProvider);
        $calculator = new TokenUsageCalculator();
        $client = new LlmClient();
        $recorder = new TokenUsageRecorder();


        $meteredClient = new MeteredLlmClient(
            $rateLimiter,
            $calculator,
            $client,
            $recorder,
        );

        $this->expectException(LlmRateLimitExceededException::class);

        $meteredClient->requestCompletion('وضعیت سفارش ORD-2024 چیست؟');
    }
}
