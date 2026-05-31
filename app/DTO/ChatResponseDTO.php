<?php

namespace App\DTO;

class ChatResponseDTO

{
    public function __construct(
        public string $id,
        public string $content,
        public string $role
    ){}

    /*
    * تبدیل ریسپانس خام اوپن ای آی به دی تی او
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