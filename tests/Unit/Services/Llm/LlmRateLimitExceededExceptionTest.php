<?php

namespace Tests\Unit\Services\Llm;

use PHPUnit\Framework\TestCase;
use App\Exceptions\LlmRateLimitExceededException;

class LlmRateLimitExceededExceptionTest extends TestCase
{
    public function test_it_can_be_created_with_a_message_and_code(): void
    {
        $exception = new LlmRateLimitExceededException(
            'LLM rate limit exceeded.',
            429,
        );

        $this->assertInstanceOf(\RuntimeException::class, $exception);

        $this->assertSame('LLM rate limit exceeded.', $exception->getMessage());

        $this->assertSame(429, $exception->getCode());
    }
}
