<?php

namespace App\Agent\Tools;

use App\Agent\Contracts\ToolInterface;

abstract class AbstractTool implements ToolInterface
{
    /**
     * Convert the tool definition into the standard OpenAI Function Calling schema.
     *
     * @return array<string, mixed>
     */
    public function toOpenAiDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => $this->getParameters(),
            ],
        ];
    }
}
