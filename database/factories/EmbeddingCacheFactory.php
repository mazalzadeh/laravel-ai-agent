<?php

namespace Database\Factories;

use App\Models\EmbeddingCache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmbeddingCache>
 */
class EmbeddingCacheFactory extends Factory
{
    protected $model=EmbeddingCache::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hash'=>fake()->unique()->sha1(),
            'text'=>fake()->paragraph(),
            'embedding'=>[0.11, 0.32, -0.77, 0.54],
            'model'=>'text-embedding-3-small',
        ];
    }

    public function withoutModel():static
    {
        return $this->state(fn()=>['model'=>null,]);
    }
}
