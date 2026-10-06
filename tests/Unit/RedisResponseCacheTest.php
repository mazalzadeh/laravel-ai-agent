<?php

namespace Tests\Unit;

use App\Agent\Services\RedisResponseCache;
use Tests\TestCase;

class RedisResponseCacheTest extends TestCase
{
    protected RedisResponseCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new RedisResponseCache(ttl: 60);
    }

    public function test_it_can_store_and_retrieve_cached_response(): void
    {
        $prompt = 'What is the capital of France?';
        $response = 'The capital of France is Paris.';

        $this->cache->forget($prompt);
        $this->assertNull($this->cache->get($prompt));

        $this->cache->set($prompt, $response);

        $this->assertEquals($response, $this->cache->get($prompt));

        $this->cache->forget($prompt);
    }


    public function test_it_can_forget_a_cached_response(): void
    {
        $prompt = 'Explain SOLID principles in simple words.';
        $response = 'SOLID is an acronym for 5 design principles...';

        $this->cache->set($prompt, $response);
        $this->assertEquals($response, $this->cache->get($prompt));

        $this->cache->forget($prompt);

        $this->assertNull($this->cache->get($prompt));
    }
}
