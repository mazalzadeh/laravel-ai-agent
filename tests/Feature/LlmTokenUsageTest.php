<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Llm\TokenUsage;
use App\Services\Llm\TokenUsageExtractor;
use App\Services\Llm\TokenUsageRecorder;
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


    public function test_it_extracts_token_usage_from_an_llm_response(): void
    {
        $extractor = new TokenUsageExtractor();

        $usage = $extractor->extract([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 15,
                'completion_tokens' => 5,
                'total_tokens' => 20,
            ],
        ]);

        $this->assertInstanceOf(TokenUsage::class, $usage);
        $this->assertSame(15, $usage->promptTokens);
        $this->assertSame(5, $usage->completionTokens);
        $this->assertSame(20, $usage->totalTokens);
    }


    public function test_it_returns_zero_usage_when_the_response_has_no_usage(): void
    {
        $extractor = new TokenUsageExtractor();

        $usage = $extractor->extract([
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello',
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(TokenUsage::class, $usage);
        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->completionTokens);
        $this->assertSame(0, $usage->totalTokens);
    }


    public function test_it_records_token_usage_with_context(): void
    {
        $recorder = new TokenUsageRecorder();

        $usage = new TokenUsage(promptTokens: 10, completionTokens: 5, totalTokens: 15);

        $recorder->record($usage, [
            'model' => 'fake-model',
            'message_count' => 2,
        ]);

        $records = $recorder->all();

        $this->assertCount(1, $records);

        $this->assertSame([
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
            'total_tokens' => 15,
        ], $records[0]['usage']);

        $this->assertSame([
            'model' => 'fake-model',
            'message_count' => 2,
        ], $records[0]['context']);
    }


    public function test_it_records_token_usage_with_empty_context_by_default(): void
    {
        $recorder = new TokenUsageRecorder();

        $recorder->record(new TokenUsage(
            promptTokens: 8,
            completionTokens: 4,
            totalTokens: 12,
        ));

        $records = $recorder->all();

        $this->assertCount(1, $records);
        $this->assertSame([], $records[0]['context']);

        $this->assertSame([
            'prompt_tokens' => 8,
            'completion_tokens' => 4,
            'total_tokens' => 12,
        ], $records[0]['usage']);
    }


    public function test_it_records_multiple_token_usage_entries(): void
    {
        $recorder = new TokenUsageRecorder();

        $recorder->record(new TokenUsage(
            promptTokens: 10,
            completionTokens: 5,
            totalTokens: 15,
        ));

        $recorder->record(new TokenUsage(
            promptTokens: 20,
            completionTokens: 10,
            totalTokens: 30,
        ));

        $records = $recorder->all();

        $this->assertCount(2, $records);
        $this->assertSame(15, $records[0]['usage']['total_tokens']);
        $this->assertSame(30, $records[1]['usage']['total_tokens']);
    }


    public function test_it_calculates_total_recorded_tokens(): void
    {
        $recorder = new TokenUsageRecorder();

        $recorder->record(new TokenUsage(
            promptTokens: 10,
            completionTokens: 5,
            totalTokens: 15,
        ));

        $recorder->record(new TokenUsage(
            promptTokens: 20,
            completionTokens: 10,
            totalTokens: 30,
        ));

        $this->assertSame(45, $recorder->totalTokens());
    }


    public function test_empty_recorder_has_zero_total_tokens(): void
    {
        $recorder = new TokenUsageRecorder();

        $this->assertSame([], $recorder->all());
        $this->assertSame(0, $recorder->totalTokens());
    }
}
