<?php

namespace App\Agent\Context;

class StepContext
{
    /**
     * @var array<int, array{name: string, input: string, output: string, decision: array<string, mixed>|null, meta: array<string, mixed>}>
     */
    private array $steps = [];

    /**
     * @param int $maxStepsForPrompt Maximum number of recent steps included in the prompt context.
     */
    public function __construct(
        private readonly int $maxStepsForPrompt = 6
    ) {}

    /**
     * Append one step to the trace.
     *
     * @param string $name Step name (e.g. "decision", "kb", "decompose", "final_answer").
     * @param string $input Input text for this step.
     * @param string $output Output text for this step.
     * @param array<string, mixed>|null $decision Optional structured decision payload (e.g. DecisionResult::toArray()).
     * @param array<string, mixed> $meta Optional metadata (timings, doc ids, etc).
     * @return void
     */
    public function addStep(
        string $name,
        string $input,
        string $output,
        ?array $decision = null,
        array $meta = []
    ): void {
        $this->steps[] = [
            'name' => $name,
            'input' => $input,
            'output' => $output,
            'decision' => $decision,
            'meta' => $meta,
        ];
    }


    /**
     * Export the full trace.
     *
     * @return array{steps: array<int, array{name: string, input: string, output: string, decision: array<string, mixed>|null, meta: array<string, mixed>}>}
     */
    public function toArray(): array
    {
        return ['steps' => $this->steps];
    }


    /**
     * Convert recent steps into a compact prompt-friendly string.
     *
     * @return string
     */
    public function toPromptContext(): string
    {
        if (empty($this->steps)) {
            return '';
        }

        $limit = max(1, $this->maxStepsForPrompt);
        $recent = array_slice($this->steps, -$limit);

        $chunks = [];
        foreach ($recent as $index => $step) {
            $chunks[] = sprintf(
                "[Step %d] %s\nInput: %s\nOutput: %s",
                $index + 1,
                $step['name'],
                $step['input'],
                $step['output']
            );
        }

        return implode("\n\n", $chunks);
    }
}
