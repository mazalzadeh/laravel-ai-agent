<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\LlmRateLimiter;
use PHPUnit\Framework\TestCase;
use App\Exceptions\LlmRateLimitExceededException;
use Mockery;

class LlmRateLimiterTest extends TestCase
{
    protected function tearDown():void
    {
        Mockery::close();
    }


    public function test_it_throws_exception_when_rate_limit_is_exceeded(): void
    {
        // Arrange: Mocking a service that provides current usage and limit
        $usageProvider = Mockery::mock('alias:App\Services\Llm\UsageProvider');
        $usageProvider->shouldReceive('getCurrentUsage')
            ->once()
            ->andReturn(1000); //Example current usage
        $usageProvider->shouldReceive('getRateLimit')
            ->once()
            ->andReturn(900);

        $limiter = new LlmRateLimiter($usageProvider);

        // Assert: Expecting the exception to be thrown
        $this->expectException(LlmRateLimitExceededException::class);
        $this->expectExceptionMessage('LLM rate limit exceeded.');

        // Act: Call the method that should trigger the exception
        $limiter->checkLimit();
    }


    public function test_it_does_not_throw_exception_when_within_limit(): void
    {
        // Arrange: Mocking a service that provides current usage and limit
        $usageProvider = Mockery::mock('alias:App\Services\Llm\UsageProvider');
        $usageProvider->shouldReceive('getCurrentUsage')
            ->once()
            ->andReturn(800); //Example current usage
        $usageProvider->shouldReceive('getRateLimit')
            ->once()
            ->andReturn(900); //Example rate limit

        $limiter = new LlmRateLimiter($usageProvider);

        // Act: Call the method that should not trigger the exception
        $limiter->checkLimit();

        // Assert: No exception should be thrown, so this line will be reached if success
        $this->assertTrue(true);
    }
}
