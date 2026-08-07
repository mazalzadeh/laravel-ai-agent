<?php

namespace App\Services\Llm\Contracts;

interface LlmClient
{
    /**
     * Sends a request to the LLM.
     *
     * @param string $prompt
     * @return array
     */
    public function requestCompletion(string $prompt):array;
}
