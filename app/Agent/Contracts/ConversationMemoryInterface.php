<?php

namespace App\Agent\Contracts\ConversationMemoryInterface;

interface ConversationMemoryInterface
{
    /**
     * Add a message to the conversation history.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @param string $role The sender role (e.g., 'user', 'assistant', 'system').
     * @param string $content The text content of the message.
     * @return void
     */
    public function addMessage(string $sessionId, string $role, string $content): void;

    /**
     * Retrieve all messages stored for a specific session.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @return array<int, array{role: string, content: string}> List of messages.
     */
    public function getMessage(string $sessionId): array;

    /**
     * Clear all conversation history for a given session.
     *
     * @param string $sessionId Unique identifier for the conversation session.
     * @return bool True if successfully deleted, false otherwise.
     */
    public function clear(string $sessionId): bool;
}
