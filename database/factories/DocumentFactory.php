<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraphs(3, true),
            'embedding' => [0.12, -0.44, 0.91, 0.03, 0.77],
        ];
    }

    public function withChunks(int $count = 3): static
    {
        return $this->has(
            \App\Models\DocumentChunk::factory()
                ->count($count)
                ->state(new \Illuminate\Database\Eloquent\Factories\Sequence(
                    ...array_map(
                        fn($i) => ['chunk_index' => $i],
                        range(0, $count - 1)
                    )
                )),
            'chunks'
        );
    }
}
