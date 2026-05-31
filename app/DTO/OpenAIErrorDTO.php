<?php

namespace App\DTO;

class OpenAIErrorDTO
{
    public function __construct(
        public string $type,
        public string $message,
        public ?string $status = null
    ){}

    public static function fromArray(array $data, int $status = 0): self
    {
        return new self(
            type: $data['error']['type'] ?? 'unknown_error',
            message: $data['error']['message'] ?? 'An unknown error occurred.',
            status: $status
        );
    }
}