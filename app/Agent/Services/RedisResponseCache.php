<?php

namespace App\Agent\Services;

use App\Agent\Contracts\ResponseCacheInterface;
use Illuminate\Support\Facades\Redis;

class RedisResponseCache implements ResponseCacheInterface
{
    /**
     * The Redis key prefix for cached responses.
     */
    protected const KEY_PREFIX = 'agent:cache:response:';


    /**
     * Default Time-to-Live in seconds (e.g., 86400 seconds = 24 hours).
     */
    protected int $defaultTtl;


    /**
     * RedisResponseCache constructor.
     *
     * @param int $ttl Default cache TTL in seconds.
     */
    public function __construct(int $ttl = 86400)
    {
        $this->defaultTtl = $ttl;
    }


    /**
     * Retrieve a cached response for a given prompt.
     *
     * @param string $prompt
     * @return string|null
     */
    public function get(string $prompt): ?string
    {
        $key = $this->getKey($prompt);

        $cached = Redis::get($key);

        return $cached !== null ? (string) $cached : null;
    }


    /**
     * Store a response in the cache for a given prompt.
     *
     * @param string $prompt
     * @param string $response
     * @param int|null $ttl
     * @return void
     */
    public function set(
        string $prompt,
        string $response,
        ?int $ttl = null
    ): void {
        $key = $this->getKey($prompt);
        $duration = $ttl ?? $this->defaultTtl;

        Redis::setex($key, $duration, $response);
    }


    /**
     * Remove a specific prompt response from the cache.
     *
     * @param string $prompt
     * @return void
     */
    public function forget(string $prompt): void
    {
        $key = $this->getKey($prompt);

        Redis::del($key);
    }


    /**
     * Generate a unique Redis key using SHA-256 hash of the normalized prompt.
     *
     * @param string $prompt
     * @return string
     */
    protected function getKey(string $prompt): string
    {
        $normalizedPrompt = trim($prompt);
        $hash = hash('sha256', $normalizedPrompt);

        return self::KEY_PREFIX . $hash;
    }
}
