<?php

namespace App\Agent\Contracts;

interface ToolInterface
{
    /**
     * Get the unique name of the tool.
     *
     * @return string
     */
    public function getName(): string;


    /**
     * Get the description of the tool explaining when and how to use it.
     *
     * @return string
     */
    public function getDescription(): string;


    /**
     * Get the JSON schema definition of the tool's input parameters.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array;


    /**
     * Execute the tool with the provided arguments.
     *
     * @param array<string, mixed> $arguments
     * @return string
     */
    public function execute(array $arguments): string;


    /**
     * Convert the tool definition into the standard OpenAI Function Calling schema.
     *
     * @return array<string, mixed>
     */
    public function toOpenAiDefinition(): array;
}
