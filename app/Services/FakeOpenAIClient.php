<?php
namespace App\Services;
use Generator;

class FakeOpenAIClient implements AIClientInterface
{
    public function chat(array $message, array $options = []): array
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
        $words = ["این", " یک", " پاسخ", " آزمایشی", " به", " صورت", " استریم", " است."];

        foreach ($words as $word) {
            yield $word;
            usleep(200000); // ۲۰۰ میلی‌ثانیه صبر برای شبیه‌سازی سرعت شبکه
        }

        // جملاتی که شامل مارک‌داون هستند برای تست UI
        /*$chunks = [
            "سلام! این یک **تست مارک‌داون** است.\n\n",
            "در اینجا یک نمونه کد PHP برای شما می‌نویسم:\n",
            "
            ```php\n",
            "public function ",
            "hello() {\n",
            "    echo ",
            "'Hello World!';\n",
            "}\n",
            "
            ```\n",
            "امیدوارم خوشت اومده باشه! 😊"
        ];

        foreach ($chunks as $chunk) {
            yield $chunk;
            usleep(300000); // وقفه ۳۰۰ میلی‌ثانیه‌ای برای دیدن حالت تایپ زنده
        }*/
    }

    public function embed(string $text): array
    {
        $hash = md5($text);

        $vector = [];

        for ($i = 0; $i < 16; $i++) {
            $chunk = substr($hash, $i * 2, 2);
            $vector[] = hexdec($chunk) / 255;
        }

        return $vector;
    }
}