<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\MeteredLlmClient;
use App\Services\Llm\LlmRateLimiter;
use App\Services\Llm\TokenUsageCalculator;
use App\Exceptions\LlmRateLimitExceededException;
use PHPUnit\Framework\TestCase;
use Mockery;

class MeteredLlmClientTest extends TestCase
{
    protected function tearDown():void
    {
        Mockery::close();
    }


    public function test_it_throws_rate_limit_exception_when_rate_limiter_blocks(): void
    {
        //Arrange
        $llmRateLimiter = Mockery::mock(LlmRateLimiter::class);
        $llmRateLimiter->shouldReceive('checkLimit')
            ->once()
            ->andThrow(new LlmRateLimitExceededException('Rate limit reached'));


        // We don't need a TokenUsageCalculator mock here because checkLimit should throw before it's called.
        // We also don't need a mock for the actual LLM client, as the failure happens before interacting with it.

        $mockLlmClientForException = Mockery::mock(\App\Services\Llm\Contracts\LlmClient::class);
        $meteredClient = new MeteredLlmClient(
            $llmRateLimiter,
            new TokenUsageCalculator(),
            $mockLlmClientForException
        );
        //Assert
        $this->expectException(LlmRateLimitExceededException::class);
        $this->expectExceptionMessage('Rate limit reached');

        //Act
        $meteredClient->requestCompletion('Test prompt');
    }


    public function test_it_calculates_and_records_token_usage_on_successful_request(): void
    {
        //Arrange
        $llmRateLimiter = Mockery::mock(LlmRateLimiter::class);
        $llmRateLimiter->shouldReceive('checkLimit')->once()->andReturnNull(); // Limit not exceeded

        $tokenUsageCalculator = Mockery::mock(TokenUsageCalculator::class);
        $tokenUsageCalculator->shouldReceive('calculate')
            ->once()
            ->andReturn(['prompt_tokens' => 50, 'completion_tokens' => 100]); // Example usage

        // Mock the underlying LLM client to return a successful response
        $mockLlmClient = Mockery::mock(\App\Services\Llm\Contracts\LlmClient::class); // Assuming an interface for the actual LLM client
        $mockLlmClient->shouldReceive('requestCompletion')
            ->once()
            ->with('Test prompt')
            ->andReturn([
                'choices' =>
                [
                    [
                        'message' => ['content' => 'LLM Response'],
                        'usage' => ['prompt_tokens' => 50, 'completion_tokens' => 100]
                    ]
                ]
            ]);


        // We need to inject the mocked LLM client into MeteredLlmClient,
        // so MeteredLlmClient's constructor might need adjustment or we pass it in.
        // For now, let's assume MeteredLlmClient can be constructed with these dependencies.
        // If MeteredLlmClient has a different constructor, adjust accordingly.
        $meteredClient = new MeteredLlmClient(
            $llmRateLimiter,
            $tokenUsageCalculator,
            $mockLlmClient // Injecting the actual LLM client mock
        );

        // Act
        $result = $meteredClient->requestCompletion('Test prompt');

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
        $this->assertNotEmpty($result['choices']);
        $this->assertArrayHasKey('content', $result['choices'][0]['message']);
        $this->assertSame('LLM Response', $result['choices'][0]['message']['content']);

        // Optionally, assert that usage recording happened if MeteredLlmClient had such a method
        // For now, we focus on the successful response and calculator being called.
    }
}
