<?php

namespace App\Agent\DTO;

readonly class DecisionResult
{
    /**
     * DecisionResult constructor.
     *
     * @param string $action The action/route chosen by the decision-making step.
     * @param array<string, mixed> $parameters The extracted parameters or payload required for the action.
     * @param string $reasoning The explanation or chain-of-thought behind making this decision.
     */
    public function __construct(
        public string $action,
        public array $parameters = [],
        public string $reasoning = ''
    ) {}


    /**
     * Create a DecisionResult instance from an associative array.
     *
     * @param array<string, mixed> $data The payload containing action, parameters, and reasoning.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            action: (string)($data['action'] ?? ''),
            parameters: (array)($data['parameters'] ?? []),
            reasoning: (string)($data['reasoning'] ?? ''),
        );
    }


    /**
     * Convert the DecisionResult instance into an associative array.
     *
     * @return array{action: string, parameters: array<string, mixed>, reasoning: string}
     */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'parameters' => $this->parameters,
            'reasoning' => $this->reasoning,
        ];
    }


    public function isAction(string $action): bool
    {
        return strcasecmp($this->action, $action) === 0;
    }
}
