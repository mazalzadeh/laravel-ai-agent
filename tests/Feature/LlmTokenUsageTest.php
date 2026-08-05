<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Llm\TokenUsage;
use Tests\TestCase;

class LlmTokenUsageTest extends TestCase
{
    public function test_it_creates_token_usage_from_an_array(): void
    {
        $usage = TokenUsage::fromArray([
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
            'total_tokens' => 15,
        ]);

        $this->assertSame(10, $usage->promptTokens);
        $this->assertSame(5, $usage->completionTokens);
        $this->assertSame(15, $usage->totalTokens);
    }


    public function test_it_converts_token_usage_to_an_array(): void
    {
        $usage = new TokenUsage(
            promptTokens: 10,
            completionTokens: 5,
            totalTokens: 15,
        );

        $this->assertSame([
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
            'total_tokens' => 15,
        ], $usage->toArray());
    }

    public function test_it_defaults_missing_token_values_to_zero(): void
    {
        $usage = TokenUsage::fromArray([]);

        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->completionTokens);
        $this->assertSame(0, $usage->totalTokens);
    }
}
