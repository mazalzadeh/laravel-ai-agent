<?php

namespace Database\Factories;

use App\Models\DocumentChunk;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentChunk>
 */
class DocumentChunkFactory extends Factory
{
    protected $model = DocumentChunk::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'chunk_index' => 0,
            'content' => fake()->sentence(20),
            'embedding' => [0.21, 0.67, -0.15, 0.49],
        ];
    }

    public function withoutEmbedding(): static
    {
        return $this->state(fn() => ['embedding' => null,]);
    }
}
