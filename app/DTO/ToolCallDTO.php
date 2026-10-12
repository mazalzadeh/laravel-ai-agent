<?php

namespace App\DTO;

class ToolCallDTO
{
    /**
     * ToolCallDTO constructor.
     *
     * @param string $id Unique identifier for the tool call.
     * @param string $name Name of the function/tool to execute.
     * @param array<string, mixed> $arguments Parsed arguments required by the tool.
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $arguments = []
    ) {}

    /**
     * Create a DTO instance from an OpenAI tool call payload array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $argumentsRaw = $data['function']['arguments'] ?? '{}';
        $arguments = is_array($argumentsRaw)
            ? $argumentsRaw
            : (json_decode($argumentsRaw, true) ?: []);

        return new self(
            id: $data['id'] ?? '',
            name: $data['function']['name'] ?? '',
            arguments: $arguments
        );
    }
}
