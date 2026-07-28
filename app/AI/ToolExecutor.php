<?php

namespace App\AI;

use RuntimeException;

/**
 * Execute registered tools by name and pass their arguments to the selected tool.
 */
class ToolExecutor
{
    /**
     * Create a new ToolExecutor instance.
     *
     * @param \App\AI\ToolRegistry $registry The registry used to resolve tools by name.
     * @return void
     */
    public function __construct(protected ToolRegistry $registry)
    {}

     /**
     * Execute a registered tool with the provided arguments.
     *
     * @param string $name The unique name of the tool to execute.
     * @param array $arguments The arguments received from the model tool call.
     * @return array The tool execution result as an associative array.
     *
     * @throws \RuntimeException If the requested tool is not registered.
     */
    public function execute(string $name,array $arguments):array
    {
        $tool=$this->registry->find($name);

        if(!$tool){
            throw new RuntimeException("Tool [{$name}] is not registered in the system.");
        }

        return $tool->execute($arguments);
    }
}
