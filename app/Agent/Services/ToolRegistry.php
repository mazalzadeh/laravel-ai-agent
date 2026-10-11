<?php

namespace App\Agent\Services;

use App\Agent\Contracts\ToolInterface;
use InvalidArgumentException;

class ToolRegistry
{
    /**
     * Registered tools mapped by their unique names.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];


    /**
     * Register a tool in the registry.
     *
     * @param ToolInterface $tool
     * @return void
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }


    /**
     * Check if a tool is registered by name.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }


    /**
     * Retrieve a registered tool by its name.
     *
     * @param string $name
     * @return ToolInterface
     *
     * @throws InvalidArgumentException
     */
    public function get(string $name): ToolInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("Tool '{$name}' is not registered.");
        }

        return $this->tools[$name];
    }


    /**
     * Get all registered tools formatted for OpenAI API tools payload.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toOpenAiTools(): array
    {
        $openAiTools = [];

        foreach ($this->tools as $tool) {
            $openAiTools[] = $tool->toOpenAiDefinition();
        }

        return $openAiTools;
    }


    /**
     * Get all registered tools as an associative array.
     *
     * @return array<string, ToolInterface>
     */
    public function all(): array
    {
        return $this->tools;
    }
}
