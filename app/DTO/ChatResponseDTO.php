<?php

namespace App\DTO;

class ChatResponseDTO

{
    /**
     * ChatResponseDTO constructor.
     *
     * @param string $id Unique completion identifier.
     * @param string|null $content Assistant message content (null when only tool calls are present).
     * @param string $role The role of the message author.
     * @param array<ToolCallDTO> $toolCalls List of tool calls requested by the model.
     */
    public function __construct(
        public string $id,
        public ?string $content,
        public string $role = 'assistant',
        public array $toolCalls = []
    ) {}

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
        $message = $data['choices'][0]['message'] ?? [];
        $rawToolCalls = $message['tool_calls'] ?? [];

        $toolCalls = [];
        foreach ($rawToolCalls as $call) {
            $toolCalls[] = ToolCallDTO::fromArray($call);
        }

        return new self(
            id: $data['id'] ?? '',
            content: $message['content'] ?? null,
            role: $message['role'] ?? 'assistant',
            toolCalls: $toolCalls
        );
    }

    /**
     * Determine whether the response contains one or more tool calls.
     *
     * @return bool
     */
    public function hasToolCalls(): bool
    {
        return ! empty($this->toolCalls);
    }

    /**
     * Get the list of requested tool calls.
     *
     * @return array<ToolCallDTO>
     */
    public function getToolCalls(): array
    {
        return $this->toolCalls;
    }
}
