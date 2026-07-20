<?php

namespace App\DTO;

class OpenAIErrorDTO
{
    public function __construct(
        public string $type,
        public string $message,
        public ?string $status = null
    ){}

    /**
     * Create an error DTO from the raw API error response.
     *
     * Maps the error payload returned by the API into a normalized DTO and uses
     * fallback values when expected error fields are missing.
     *
     * @param array $data The raw error response data.
     * @param int $status The HTTP status code associated with the error response.
     *
     * @return self A DTO containing the normalized error details.
     */
    public static function fromArray(array $data, int $status = 0): self
    {
        return new self(
            type: $data['error']['type'] ?? 'unknown_error',
            message: $data['error']['message'] ?? 'An unknown error occurred.',
            status: $status > 0 ? (string)$status : null
        );
    }
}
