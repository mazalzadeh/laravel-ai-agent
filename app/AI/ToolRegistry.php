<?php

namespace App\AI;

use App\Ai\Tools\ToolInterface;

/**
 * Manage the collection of registered tools and expose them in API-ready format.
 */
class ToolRegistry
{
    /**
     * The registered tools indexed by their unique names.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

    /**
     * Register a new tool in the registry.
     *
     * If a tool with the same name already exists, it will be replaced.
     *
     * @param \App\AI\Tools\ToolInterface $tool The tool instance to register.
     * @return void
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * Retrieve a registered tool by its name.
     *
     * @param string $name The unique tool name.
     * @return \App\AI\Tools\ToolInterface|null The matched tool instance, or null if not found.
     */
    public function find(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Return all registered tools.
     *
     * @return array<string, \App\AI\Tools\ToolInterface> The list of registered tools keyed by tool name.
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Convert all registered tools into the OpenAI-compatible tools payload.
     *
     * Each tool is mapped to the `type=function` structure expected by the API.
     *
     * @return array<int, array<string, mixed>> The formatted tools payload for the LLM API.
     */
    public function toApiFormat(): array
    {
        $formatted = [];

        foreach ($this->tools as $tool) {
            $formatted[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters()
                ]
            ];
        }

        return $formatted;
    }
}
