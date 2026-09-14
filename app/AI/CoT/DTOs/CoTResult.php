<?php

declare(strict_types=1);

namespace App\AI\CoT\DTOs;

/**
 * Class CoTResult
 *
 * Data Transfer Object representing the extracted Chain-of-Thought reasoning
 * and the clean user-facing response.
 *
 *
 */
final class CoTResult
{
    private ?string $reasoning;

    private string $answer;


    /**
     * CoTResult constructor.
     *
     * @param string|null $reasoning Internal reasoning text or null if omitted.
     * @param string $answer Final user-facing answer.
     */
    public function __construct(
        ?string $reasoning,
        string $answer
    ) {
        $this->reasoning = $reasoning;
        $this->answer = $answer;
    }


    /**
     * Retrieve the internal reasoning process.
     *
     * @return string|null
     */
    public function getReasoning(): ?string
    {
        return $this->reasoning;
    }


    /**
     * Retrieve the clean final answer.
     *
     * @return string
     */
    public function getAnswer(): string
    {
        return $this->answer;
    }


    /**
     * Check if reasoning steps exist in this result.
     *
     * @return bool
     */
    public function hasReasoning(): bool
    {
        return $this->reasoning !== null && trim($this->reasoning) !== '';
    }
}
