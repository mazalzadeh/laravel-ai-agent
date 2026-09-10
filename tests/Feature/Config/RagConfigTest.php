<?php

namespace Tests\Feature\Config;

use App\AI\Prompts\Templates\RagPrompt;
use Tests\TestCase;

class RagConfigTest extends TestCase
{
    public function test_rag_config_file_has_all_required_keys(): void
    {
        $config = config('rag');

        $this->assertIsArray($config);

        $this->assertArrayHasKey('max_documents', $config);
        $this->assertArrayHasKey('max_context_length', $config);
        $this->assertArrayHasKey('max_context_tokens', $config);
        $this->assertArrayHasKey('chunk_size', $config);
        $this->assertArrayHasKey('chunk_overlap', $config);
        $this->assertArrayHasKey('seperator', $config);

        $this->assertArrayHasKey('min_score', $config);
        $this->assertArrayHasKey('empty_fallback', $config);
        $this->assertArrayHasKey('prompts', $config);
        $this->assertArrayHasKey('default', $config['prompts']);
    }

    public function test_rag_config_default_values(): void
    {
        $this->assertSame(5, config('rag.max_documents'));
        $this->assertSame(0.7, config('rag.min_score'));
        $this->assertSame(4000, config('rag.max_context_length'));
        $this->assertSame('No relevant context found.', config('rag.empty_fallback'));
        $this->assertSame(RagPrompt::class, config('rag.prompts.default'));
    }
}
