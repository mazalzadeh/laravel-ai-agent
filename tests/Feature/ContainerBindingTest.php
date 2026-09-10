<?php

namespace Tests\Feature;

use App\AI\Context\ContextFormatter;
use App\AI\Context\ContextRetriever;
use App\AI\Prompts\Templates\RagPrompt;
use App\AI\Prompts\ContextAwarePromptExecutor;
use App\AI\Context\ContextInjectionService;
use App\AI\Context\PlainTextContextFormatter;
use App\Services\RagService;
use App\AI\Context\VectorContextRetriever;
use Tests\TestCase;

class ContainerBindingTest extends TestCase
{
    public function test_context_retriever_is_bound_to_vector_context_retriever(): void
    {
        $instance = $this->app->make(ContextRetriever::class);

        $this->assertInstanceOf(VectorContextRetriever::class, $instance);
    }

    public function test_context_formatter_is_bound_to_plain_text_context_formatter(): void
    {
        $instance = $this->app->make(ContextFormatter::class);

        $this->assertInstanceOf(PlainTextContextFormatter::class, $instance);
    }

    public function test_context_injection_service_can_be_resolved(): void
    {
        $instance = $this->app->make(ContextInjectionService::class);

        $this->assertInstanceOf(ContextInjectionService::class, $instance);
    }

    public function test_context_aware_prompt_executor_can_be_resolved(): void
    {
        $instance = $this->app->make(ContextAwarePromptExecutor::class);

        $this->assertInstanceOf(ContextAwarePromptExecutor::class, $instance);
    }

    public function test_rag_service_can_be_resolved(): void
    {
        $instance = $this->app->make(RagService::class);

        $this->assertInstanceOf(RagService::class, $instance);
    }
}
