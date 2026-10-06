<?php

namespace App\Agent\Contracts;

interface ResponseCacheInterface
{
    /**
     * Retrieve a cached response for a given prompt.
     *
     * @param string $prompt The input prompt used as a cache key.
     * @return string|null The cached response or null if not found.
     */
    public function get(string $prompt): ?string;

    /**
     * Store a response in the cache for a given prompt.
     *
     * @param string $prompt The input prompt.
     * @param string $response The LLM generated response.
     * @param int|null $ttl Time-to-live in seconds.
     * @return void
     */
    public function set(string $prompt, string $response, ?int $ttl = null): void;

    /**
     * Remove a specific prompt response from the cache.
     *
     * @param string $prompt
     * @return void
     */
    public function forget(string $prompt): void;
}
