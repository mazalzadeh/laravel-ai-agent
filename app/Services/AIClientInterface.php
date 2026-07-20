<?php

namespace App\Services;

interface AIClientInterface
{
    /**
     * Send chat messages to the AI provider and return a structured response.
     *
     * Defines the contract for submitting chat messages along with optional
     * generation parameters and receiving a normalized response payload.
     *
     * @param array $messages The ordered list of chat messages to send.
     * @param array $options Optional provider-specific generation settings.
     *
     * @return array A structured response containing the result of the chat request.
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Stream chat response chunks from the AI provider as they become available.
     *
     * Defines the contract for submitting chat messages and yielding incremental
     * response fragments through a generator.
     *
     * @param array $messages The ordered list of chat messages to send.
     * @param array $options Optional provider-specific generation settings.
     *
     * @return \Generator A generator that yields streamed response chunks.
     */
    public function streamChat(array $messages, array $options = []): \Generator;

    /**
     * Generate an embedding vector for the given text.
     *
     * Defines the contract for converting input text into a numeric embedding
     * representation suitable for similarity search or other vector-based tasks.
     *
     * @param string $text The input text to embed.
     *
     * @return array The embedding vector generated from the input text.
     */
    public function embed(string $text): array;
}
