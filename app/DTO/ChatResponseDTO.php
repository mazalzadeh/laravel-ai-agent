<?php

namespace App\DTO;

class ChatResponseDTO

{
    public function __construct(
        public string $id,
        public string $content,
        public string $role
    ){}

    /**
     * Create a chat response DTO from the raw API response array.
     *
     * Extracts the response identifier, assistant message content, and role from
     * the OpenAI chat completion payload and maps them into a DTO instance.
     *
     * @param array $data The raw chat completion response data.
     *
     * @return self A DTO containing the normalized chat response fields.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            content: $data['choices'][0]['message']['content'] ?? '',
            role: $data['choices'][0]['message']['role'] ?? 'assistant'
        );
    }
}
