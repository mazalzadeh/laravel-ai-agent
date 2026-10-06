<?php

namespace App\Agent\Services;

use App\Agent\Contracts\ConversationMemoryInterface;
use Illuminate\Support\Facades\Redis;

class RedisConversationMemory implements ConversationMemoryInterface
{
    /**
     * The Redis key prefix for conversation histories.
     */
    protected const KEY_PREFIX = 'agent:conversation:';

    /**
     * Default Time-to-Live in seconds (e.g., 3600 seconds = 1 hour).
     */
    protected int $ttl;

    /**
     * Maximum number of messages to retain in memory (Sliding Window).
     */
    protected int $windowSize;

    /**
     * RedisConversationMemory constructor.
     *
     * @param int $ttl Lifetime of the session in Redis (in seconds).
     * @param int $windowSize Maximum number of messages to keep in the conversation history.
     */
    public function __construct(int $ttl = 3600, int $windowSize = 10)
    {
        $this->ttl = $ttl;
        $this->windowSize = $windowSize;
    }

    /**
     * Add a message to the conversation history in Redis.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @param string $role The sender role (e.g., 'user', 'assistant', 'system').
     * @param string $content The text content of the message.
     * @return void
     */
    public function addMessage(string $sessionId, string $role, string $content): void
    {
        $key = $this->getKey($sessionId);

        $payload = json_encode([
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toISOString(),
        ]);

        // Push message to the end of Redis List
        Redis::rpush($key, $payload);

        if($this->windowSize>0){
            Rdis::ltrim($key,$this->windowSize,-1);
        }

        // Refresh session expiration TTL
        Redis::expire($key, $this->ttl);
    }

    /**
     * Retrieve all messages stored for a specific session.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @return array<int, array{role: string, content: string}> List of messages.
     */
    public function getMessage(string $sessionId): array
    {
        $key = $this->getKey($sessionId);

        $rawMessage = Redis::lrange($key, 0, -1);

        if (empty($rawMessage)) {
            return [];
        }

        return array_map(function ($item) {
            $decoded = json_decode($item, true);
            return [
                'role' => $decoded['role'],
                'content' => $decoded['content'],
            ];
        }, $rawMessage);
    }

    /**
     * Clear all conversation history for a given session.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @return bool True if successfully deleted, false otherwise.
     */
    public function clear(string $sessionId): bool
    {
        $key = $this->getKey($sessionId);

        return (bool)Redis::del($key);
    }

    /**
     * Build the Redis key for the given session ID.
     *
     * @param string $sessionId
     * @return string
     */
    protected function getKey(string $sessionId): string
    {
        return self::KEY_PREFIX . $sessionId;
    }
}
