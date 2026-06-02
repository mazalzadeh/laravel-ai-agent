<?php
namespace App\Services;
use Generator;

class FakeOpenAIClient implements AIClientInterface
{
    public function chat(string $message): array
    {
        // شبیه‌سازی پاسخ موفق OpenAI
        return [
            'success' => true,
            'data'    => [
                'id'        => 'chatcmpl-fake-'.uniqid(),
                'object'    => 'chat.completion',
                'created'   => now()->timestamp,
                'model'     => 'gpt-4.1-mini',
                'choices'   => [
                    [
                        'index'   => 0,
                        'message' => [
                            'role'    => 'assistant',
                            'content' => "You said: \"{$message}\"",
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens'     => 10,
                    'completion_tokens' =>20,
                    'total_tokens'      => 30,
                ],
            ],
        ];
    }

    public function streamChat(array $messages, array $options = []): Generator
    {
        yield 'Fake response';
    }
}