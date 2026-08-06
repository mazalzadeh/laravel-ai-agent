<?php

namespace App\Services\Llm;

/**
 * Records token usage in memory for the current object lifecycle.
 */
final class TokenUsageRecorder
{
    /**
     * Recorded token usage entries.
     *
     * @var array<int, array{
     *     usage: array{
     *         prompt_tokens: int,
     *         completion_tokens: int,
     *         total_tokens: int
     *     },
     *     context: array<string, mixed>
     * }>
     */
    private array $records=[];


    /**
     * Records token usage with optional contextual information.
     *
     * @param TokenUsage $usage Token usage to record.
     * @param array<string, mixed> $context Additional information about the LLM request.
     *
     * @return void
     */
    public function record(TokenUsage $usage,array $context=[]):void
    {
        $this->records[]=[
            'usage'=>$usage->toArray(),
            'context'=>$context,
        ];
    }


    /**
     * Returns all recorded token usage entries.
     *
     * @return array<int, array{
     *     usage: array{
     *         prompt_tokens: int,
     *         completion_tokens: int,
     *         total_tokens: int
     *     },
     *     context: array<string, mixed>
     * }>
     */
    public function all():array
    {
        return $this->records;
    }


    /**
     * Calculates the sum of total tokens across all recorded entries.
     *
     * @return int
     */
    public function totalTokens():int
    {
        return array_sum(
            array_map(
                static fn (array $record):int=>$record['usage']['total_tokens'],
                $this->records,
            )
        );
    }
}
