<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_semantic_search_returns_results()
    {
        Document::create([
            'content' => 'Laravel is a PHP framework',
            'embedding' => array_fill(0, 16, 0.5)
        ]);

        $response = $this->postJson('/api/semantic-search', [
            'query' => 'PHP framework'
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'query',
            'results' => [
            [
                'content',
                'score',
            ],
        ],
        ]);
    }
}
