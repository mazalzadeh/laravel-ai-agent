<?php

namespace App\AI\Tools;

/**
 * Define the structure and execution contract for all LLM-callable tools.
 */
interface ToolInterface
{
    /**
     * Return the unique identifier name of the tool.
     *
     * This name is used by the LLM to request the execution of the tool.
     *
     * @return string The unique tool identifier.
     */
    public function name(): string;

    /**
     * Return a natural language description explaining what the tool does.
     *
     * The LLM uses this description to decide when to call the tool.
     *
     * @return string The description of the tool.
     */
    public function description(): string;


    /**
     * Return the JSON schema definition for the tool's input parameters.
     *
     * The schema defines the argument properties, their types, and required fields.
     *
     * @return array The parameters schema as an associative array.
     */
    public function parameters(): array;

    /**
     * Execute the tool logic with the arguments extracted from the LLM tool call.
     *
     * @param array $arguments The parameters passed to the tool.
     * @return array The execution result as an associative array.
     */
    public function execute(array $arguments): array;
}
