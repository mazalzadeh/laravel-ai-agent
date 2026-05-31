<?php

namespace App\Services;

// use Illuminate\Support\Facades\Http;

class AIClient
{
    // public function chat(string $message)
    // {
    //     // dd('aiclient');
    //     $response = Http::post(
    //         'http://localhost:8080/api/fake-openai/chat',['model' => 'gpt-4','message' => [['role' => 'user','content' => $message]]]);

    //     return $response->json();
    // }

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