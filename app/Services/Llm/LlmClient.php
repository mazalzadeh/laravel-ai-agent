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
        if($lastMessage['role']==='tool'){
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
    protected function simulateToolCallResponse(string $userPrompt):array
    {
        // Extracting Order ID (e.g., ORD-2024) using Regex
        preg_match('/ORD-\d+/',$userPrompt,$matches);
        $orderId=$matches[0]?? 'ORD-12345';

        return[
            'choices'=>[
                [
                    'message'=>[
                        'role'=>'assistant',
                        'content'=>null,
                        'tool_calls'=>[
                            [
                                'id'=>'call_'. Str::random(10),
                                'type'=>'function',
                                'function'=>[
                                    'name'=>'get_order_status',
                                    'arguments'=>json_encode('order_id'=>$orderId)
                                ]
                            ]
                        ]
                    ],
                    'finish_reason'=>'tool_calls'
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
    protected function simulateFinalResponse(array $messages):array
    {
        // Find the tool response in history
        $toolMessage=collect($messages)->where('role','tool')->last();
        $data=json_decode($toolMessage['content'],true);

        $status=$data['status']??'نامشخص';
        $delivery=$data['delivery_date']??'نامعلوم';

        $responseText = "با توجه به استعلام من، سفارش شما در وضعیت «{$status}» قرار دارد. " .
                        "تاریخ تقریبی تحویل این مرسوله {$delivery} و توسط شرکت {$data['courier']} ارسال شده است.";

        return $this->defaultResponse($responseText);
    }

    /**
     * Generates a standard OpenAI-style message structure.
     *
     * @param string $content
     * @return array
     */
    protected function defaultResponse(string $content):array
    {
        return[
            'choices'=>[
                [
                    'message'=>[
                        'role'=>'assistant',
                        'content'=>$content,
                    ],
                    'finish_reason'=>'stop'
                ]
            ]
        ];
    }
}
