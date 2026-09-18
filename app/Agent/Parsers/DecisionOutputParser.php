<?php

namespace App\Agent\Parsers;

use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use JsonException;

class DecisionOutputParser
{
    /**
     * Parse the raw AI output string into a DecisionResult instance.
     *
     * @param string $rawOutput The raw text output from the AI model.
     * @return DecisionResult The parsed decision outcome or a safe fallback.
     */
    public function parse(string $rawOutput): DecisionResult
    {
        $jsonString = $this->extractJson($rawOutput);

        try {
            $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return $this->createFallbackResult($rawOutput);
            }

            return DecisionResult::fromArray($data);
        } catch (JsonException) {
            return $this->createFallbackResult($rawOutput);
        }
    }


    /**
     * Extract clean JSON substring by finding outermost braces.
     *
     * @param string $text Raw text that may contain markdown or surrounding text.
     * @return string Extracted JSON string or original trimmed text.
     */

    protected function extractJson(string $text): string
    {
        $text = trim($text);

        $bt = chr(96);
        $pattern = '/' . $bt . '{3}(?:json)?\s*([\s\S]*?)\s*' . $bt . '{3}/i';

        if (preg_match($pattern, $text, $matches)) {
            $candidate = trim($matches[1]);
            if ($this->isValidJson($candidate)) {
                return $candidate;
            }
        }

        $firstBrace = strpos($text, '{');
        $lastBrace = strrpos($text, '}');

        if ($firstBrace !== false && $lastBrace !== false && $lastBrace >= $firstBrace) {
            $candidate = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
            if ($this->isValidJson($candidate)) {
                return $candidate;
            }
        }

        return $text;
    }

    /**
     * Validate whether a given string is a valid JSON.
     *
     * @param string $string
     * @return bool
     */
    protected function isValidJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Create a safe fallback DecisionResult when parsing fails.
     *
     * @param string $rawOutput The original raw text that failed parsing.
     * @return DecisionResult Default fallback decision.
     */
    protected function createFallbackResult(string $rawOutput): DecisionResult
    {
        return new DecisionResult(
            action: AgentAction::DIRECT_ANSWER->value,
            parameters: ['raw_content' => $rawOutput],
            reasoning: 'Fallback triggered due to invalid or non-JSON output from model.'
        );
    }
}
