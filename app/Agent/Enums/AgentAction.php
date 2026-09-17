<?php

namespace App\Agent\Enums;

/**
 * Enum AgentAction
 *
 * Defines the supported actions that an agent or router can select during multi-step decision making.
 */
enum AgentAction:string
{
    case DIRECT_ANSWER='DIRECT_ANSWER';
    case SEARCH_KNOWLEDGE_BASE = 'SEARCH_KNOWLEDGE_BASE';
    case DECOMPOSE_TASK = 'DECOMPOSE_TASK';

/**
     * Get a human-readable description for the action to guide the AI model.
     *
     * @return string Detailed description of when to select this action.
     */
    public function description():string
    {
        return match($this){
            self::DIRECT_ANSWER => 'Use when the user request can be answered directly using general knowledge without external data.',
            self::SEARCH_KNOWLEDGE_BASE => 'Use when the user request requires domain-specific facts, documents, or database knowledge.',
            self::DECOMPOSE_TASK => 'Use when the user request is complex and requires breaking down into sequential sub-tasks.',
        };
    }

    /**
     * Retrieve all available actions mapped to their respective descriptions.
     * Useful for dynamically injecting action choices into prompts.
     *
     * @return array<string, string> Associative array of action names to descriptions.
     */
    public static function optionsForPrompt():array
    {
        $options = [];

        foreach(self::cases() as $case){
            $options[$case->value]=$case->description();
        }

        return $options;
    }
}