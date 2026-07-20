<?php

namespace App\Services;


class AIClient
{
    /**
     * Return a mock chat completion response for the given message.
     *
     * This method simulates a chat completion payload and does not make a real API
     * request. It is useful for testing or local development when a fake response
     * structure is needed.
     *
     * @param string $message The user message to include in the mocked assistant reply.
     *
     * @return array A fake chat completion response structure.
     */
    public function chat(string $message)
    {
        return [
            'id' => 'chatcmpl-fake',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'You said: ' . $message,
                    ],
                    'finish_reason' => 'stop'
                ]
            ]
        ];
    }
}
