<?php

declare(strict_types=1);

namespace App\AI\CoT;

use App\AI\CoT\DTOs\CoTResult;

class CoTOutputParser
{
    /**
     * Regular expression to extract content inside <thought>...</thought> tags (case-insensitive, multiline).
     */
    private const THOUGHT_REGEX = '/<thought>(.*?)<\/thought>/is';

    /**
     * Regular expression to extract content inside <answer>...</answer> tags (case-insensitive, multiline).
     */
    private const ANSWER_REGEX = '/<answer>(.*?)<\/answer>/is';


    /**
     * Parse raw LLM output into a structured CoTResult DTO.
     *
     * If structured tags are absent, it safely falls back to returning
     * the entire raw content as the answer without reasoning.
     *
     * @param string $rawOutput Raw response string from the model.
     * @return CoTResult
     */
    public function parse(string $rawOutput): CoTResult
    {
        $hasThoughtTag = preg_match(self::THOUGHT_REGEX, $rawOutput, $thoughtMatches);
        $hasAnswerTag = preg_match(self::ANSWER_REGEX, $rawOutput, $answerMatches);

        if (!$hasThoughtTag && !$hasAnswerTag) {
            return new CoTResult(null, trim($rawOutput));
        }

        $reasoning = $hasThoughtTag ? trim($thoughtMatches[1]) : null;
        $answer = $hasAnswerTag ? trim($answerMatches[1]) : '';

        return new CoTResult($reasoning, $answer);
    }
}
