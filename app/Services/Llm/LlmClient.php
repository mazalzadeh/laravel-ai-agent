<?php

namespace App\Services\Llm;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class LlmClient
 *
 * Provides a fake implementation of an LLM client for local development
 * and testing of Function Calling infrastructure in Laravel 12.
 */
class LlmClient
{
    /**
     * Sends messages to the fake LLM and returns a simulated OpenAI-style response.
     *
     * @param array $messages The conversation history.
     * @param array $tools Available tools for function calling.
     * @return array Simulated response from the LLM.
     */
    public function chat(array $messages, array $tools = []): array
    {
        $lastMessage = end($messages);

        // Scenario 1: User asks a question -> LLM triggers a tool call
        if ($lastMessage['role'] === 'user') {
            return $this->simulateToolCallResponse($lastMessage['content']);
        }

        // Scenario 2: Tool output is provided -> LLM provides final human-like answer
        if ($lastMessage['role'] === 'tool') {
            return $this->simulateFinalResponse($messages);
        }

        return $this->defaultResponse('متاسفانه متوجه درخواست شما نشدم.');
    }


    /**
     * Simulates a tool call response by detecting an Order ID pattern.
     *
     * @param string $userPrompt
     * @return array
     */
    protected function simulateToolCallResponse(string $userPrompt): array
    {
        // Extracting Order ID (e.g., ORD-2024) using Regex
        preg_match('/ORD-\d+/', $userPrompt, $matches);
        $orderId = $matches[0] ?? 'ORD-12345';

        return [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_' . Str::random(10),
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_order_status',
                                    'arguments' => json_encode(['order_id' => $orderId])
                                ]
                            ]
                        ]
                    ],
                    'finish_reason' => 'tool_calls'
                ]
            ]
        ];
    }

    /**
     * Simulates the final textual response after receiving tool results.
     *
     * @param array $messages
     * @return array
     */
    protected function simulateFinalResponse(array $messages): array
    {
        $toolMessage = $messages[array_key_last($messages)];

        $toolResult = json_decode($toolMessage['content'] ?? '{}', true);

        if (!is_array($toolResult)) {
            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'متأسفم، نتیجهٔ ابزار قابل پردازش نیست.',

                        ]
                    ]
                ]
            ];
        }

        if (($toolResult['success'] ?? true) === false) {
            $errorType = $toolResult['error']['type'] ?? 'unknown_error';

            $errorMessage = $toolResult['error']['message'] ?? 'خطایی نامشخص هنگام اجرای ابزار رخ داد.';

            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => sprintf(
                                'متأسفم، اجرای ابزار با خطا مواجه شد (%s): %s',
                                $errorType,
                                $errorMessage
                            ),
                        ]
                    ]
                ]
            ];
        }

        return [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => sprintf(
                            'وضعیت سفارش %s: %s. شرکت حمل: %s، شماره رهگیری: %s، '
                                . 'زمان تقریبی تحویل: %s، وضعیت پرداخت: %s.',
                            $toolResult['order_id'],
                            $toolResult['status'],
                            $toolResult['carrier'],
                            $toolResult['tracking_number'],
                            $toolResult['estimated_delivery'],
                            $toolResult['payment_status'],
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * Generates a standard OpenAI-style message structure.
     *
     * @param string $content
     * @return array
     */
    protected function defaultResponse(string $content): array
    {
        return [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop'
                ]
            ]
        ];
    }
}
