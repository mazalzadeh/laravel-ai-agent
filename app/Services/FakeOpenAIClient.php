<?php

namespace App\Services;

use Generator;

class FakeOpenAIClient implements AIClientInterface
{
    /**
     * Simulate a successful OpenAI chat completion response.
     *
     * This fake client is intended for testing or local development where no real
     * API request should be sent. It returns a mock response structure similar to
     * the OpenAI chat completion format.
     *
     * @param array $message The input chat message payload.
     * @param array $options Optional request options (unused in the fake client).
     *
     * @return array A fake successful chat completion response.
     */
    public function chat(array $message, array $options = []): array
    {
        // Simulate a successful OpenAI response
        return [
            'success' => true,
            'data'    => [
                'id'        => 'chatcmpl-fake-' . uniqid(),
                'object'    => 'chat.completion',
                'created'   => now()->timestamp,
                'model'     => 'gpt-4.1-mini',
                'choices'   => [
                    [
                        'index'   => 0,
                        'message' => [
                            'role'    => 'assistant',
                            //'content' => "You said: \"{$message}\"",
                            'content' => 'You said: "' . json_encode($message) . '"',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens'     => 10,
                    'completion_tokens' => 20,
                    'total_tokens'      => 30,
                ],
            ],
        ];
    }

    /**
     * Simulate a streamed chat response.
     *
     * This fake implementation yields small chunks over time to mimic a real
     * streaming API response from OpenAI. Useful for testing SSE, websocket,
     * or incremental UI rendering.
     *
     * @param array $messages The input chat messages payload.
     * @param array $options Optional streaming options (unused in the fake client).
     *
     * @return \Generator<string>
     */
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

    /**
     * Generate a deterministic fake embedding vector for a given text.
     *
     * This implementation converts the MD5 hash of the text into a fixed-size
     * numeric vector. It is useful for tests and local development where a stable,
     * repeatable embedding is needed without calling a real embedding API.
     *
     * @param string $text The input text to embed.
     *
     * @return array<int, float> A 16-dimensional normalized vector with values between 0 and 1.
     */
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
