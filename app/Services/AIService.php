<?php

namespace App\Services;

use App\AI\StructuredResponseValidator;
use App\DTO\ChatResponseDTO;
use App\DTO\OpenAIErrorDTO;
use App\Services\AIClient;
use App\Clients\OpenAIClient;
use Generator;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AIService
{
    protected AIClientInterface $client;

    public function __construct(AIClientInterface $client)
    {
        $this->client = $client;
    }

    public function chat(array $messages, array $options = []): string
    {
        $response = $this->client->chat($messages, $options);

        //success
        /*if (($response['success'] ?? false) === true) {
            $dto = $response['data'];
            //return $dto->content ?: 'No response content from AI.';
            $content = $dto['choices'][0]['message']['content'] ?? 'No response content from AI.';
            return $content ?: 'No response content from AI.';
        }*/

        if (($response['success'] ?? false) == true) {
            $data = $response['data'];

            if ($data instanceof ChatResponseDTO) {
                return $data->content ?: 'No response content from AI.';
            }

            $content = $data['choices'][0]['message']['content'] ?? null;

            return $content ?: 'No response content from AI.';
        }

        //error
        $error = $response['error'] ?? null;

        if ($error instanceof OpenAIErrorDTO) {
            //manage different errors
            return match ($error->type) {
                'rate_limit_exceeded' => 'Too many requests — try again later.',
                'invalid_api_key'     => 'API Key is invalid or expired.',
                'connection_error'    => 'AI server is currently unreachable. Please check your connection.',
                'server_error'        => 'AI server is currently unreachable. Please check your connection.',
                default               => "AI Error: " . $error->message ?: 'An unexpected error occurred.',
            };
        }
        return 'An unknown error occurred while communicating with the AI.';
    }

    public function analyzeText(string $text): array
    {
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant that outputs only valid JSON.'],
            ['role' => 'user', 'content' => "Analyze this text and return as JSON (subject, priority, summary): {$text}"]
        ];

        $options = [
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0
        ];

        $responseContent = $this->chat($messages, $options);

        return json_decode($responseContent, true) ?? [];
    }

    public function streamChat(array $messages, array $options = []): Generator
    {
        return $this->client->streamChat($messages, $options);
    }

    public function embed(string $text): array
    {
        return $this->client->embed($text);
    }

    /*public function structured(string $prompt, array $schema): array
    {
        $system = "You must retrun ONLY valid matching this schema:\n" . json_encode($schema, JSON_PRETTY_PRINT);

        $response = $this->chat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ]);

        $data = json_decode($response, true);

        //1. check JSON structure
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \RuntimeException("AI failed to retrun a valid Json string.");
        }

        //2. validate fields and types based on Schema
        StructuredResponseValidator::validate($data, $schema);
        return $data;
    }*/


    public function structured(string $prompt, array $schema): array
    {
        $system = "You must return ONLY valid JSON matching this schema:\n"
            . json_encode($schema, JSON_PRETTY_PRINT);

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ];

        $maxAttempts = 3;

        $nativeOptions = [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'structured_response',
                    'schema' => $schema
                ],
            ],
            'temperature' => 0,
        ];

        try {
            $response = $this->chat($messages, $nativeOptions);

            $data = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                StructuredResponseValidator::validate($data, $schema);
                return $data;
            }
        } catch (\Throwable $e) {
            Log::warning('Native structured output failed, falling back to retry-based flow.', [
                'message' => $e->getMessage(),
            ]);
        }

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = $this->chat($messages);

            try {
                $data = json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    throw new RuntimeException('AI failed to return a valid JSON string.');
                }

                StructuredResponseValidator::validate($data, $schema);

                return $data;
            } catch (RuntimeException $exception) {
                if ($attempt === $maxAttempts) {
                    throw $exception;
                }

                $messages[] = ['role' => 'assistant', 'content' => $response];

                $messages[] = [
                    'role' => 'user',
                    'content' => 'Your previous response was invalid. '
                        . $exception->getMessage()
                        . ' Return only the corrected JSON.',
                ];
            }
        }
        throw new RuntimeException('AI failed to return a valid structured response.');

    }
}
