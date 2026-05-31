<?php

namespace App\Services;

class FakeAIClient implements AIClientInterface
{
    public function chat(string $message) : array
    {
        return[
            'id' => 'chatcmpl-fake',
            'object' => 'chat.comoletion',
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