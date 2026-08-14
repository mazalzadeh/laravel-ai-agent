<?php

namespace App\Services;

use App\AI\StructuredResponseValidator;
use App\DTO\ChatResponseDTO;
use App\DTO\OpenAIErrorDTO;
use Generator;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use App\AI\Prompts\Contracts\PromptTemplate;
use App\AI\Prompts\PromptRenderer;

class AIService
{
    protected AIClientInterface $client;
    protected PromptRenderer $promptRenderer;


    /**
     * Create a new AI service instance with the given AI client implementation.
     *
     * Injects the AI client dependency used to perform chat, streaming, and
     * embedding operations through a shared interface abstraction.
     *
     * @param AIClientInterface $client The AI client implementation used by the service.
     * @param PromptRenderer|null $promptRenderer The renderer used to resolve dynamic prompt variables.
     */
    public function __construct(
        AIClientInterface $client,
        ?PromptRenderer $promptRenderer=null
        )
    {
        $this->client = $client;
        $this->promptRenderer=$promptRenderer??new PromptRenderer();
    }

    /**
     * Execute a chat request and resolve the response into a user-friendly string.
     *
     * Sends the provided messages and options to the AI client, then normalizes
     * the result into a plain text response. On success, it extracts the assistant
     * content from either a ‍DTO instance or a raw response array. On failure, it
     * maps known AI error types to readable messages and falls back to a generic
     * error string when the error cannot be classified.
     *
     * @param array $messages The chat messages to send to the AI model.
     * @param array $options Optional generation or request options.
     *
     * @return string The assistant response content or a human-readable error message.
     */
    public function chat(array $messages, array $options = []): string
    {
        $response = $this->client->chat($messages, $options);

        //success
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

    /**
     * Analyze the given text to extract its subject, priority, and summary.
     *
     * Sends a structured prompt to the AI model asking for a specific JSON layout
     * containing subject, priority, and summary keys. By setting the response format
     * to JSON and the temperature to 0, it enforces deterministic, structured outputs.
     * The raw JSON string returned by the chat API is decoded into an associative PHP array.
     *
     * @param string $text The input text content to be analyzed.
     *
     * @return array{subject?: string, priority?: string, summary?: string} The structured analysis result, or an empty array on failure.
     */
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

    /**
     * Proxy the streaming chat request to the underlying AI client.
     *
     * This method delegates the streaming conversation to the client implementation.
     * It returns a PHP Generator, allowing the caller to iterate over response
     * fragments as they arrive from the AI provider in real-time.
     *
     * @param array $messages The collection of messages representing the chat history.
     * @param array $options  Optional parameters to tune the AI model's behavior.
     *
     * @return \Generator Yields incremental string fragments of the AI's response.
     */
    public function streamChat(array $messages, array $options = []): Generator
    {
        return $this->client->streamChat($messages, $options);
    }

    /**
     * Generate a numeric vector representation (embedding) for the provided text.
     *
     * Forwards the input text to the underlying AI client's embedding model.
     * The resulting array (vector) represents the semantic meaning of the text,
     * which can be used for similarity searches or clustering in a vector database.
     *
     * @param string $text The input string to be converted into a vector.
     *
     * @return array The numerical vector array representing the text's semantic features.
     */
    public function embed(string $text): array
    {
        return $this->client->embed($text);
    }

    /**
     * Execute a structured response request enforcing strict compliance with a given JSON schema.
     *
     * This method runs a dual-layered verification flow:
     * 1. Native API Enforcement: Attempts to retrieve the response using the client's native
     *    `json_schema` response format with a deterministic temperature (0).
     * 2. Self-Correcting Fallback Loop: If the native approach fails or validation throws an exception,
     *    it falls back to a manual retry loop (up to 3 attempts). It appends the invalid output and
     *    validation error to the message history, prompting the AI to self-correct and output a valid structure.
     *
     * @param string $prompt The user prompt instructions.
     * @param array  $schema The JSON Schema array specifying the required keys and types.
     *
     * @throws \RuntimeException If all retry attempts exhaust without generating a valid schema-compliant response.
     * @return array The decoded associative array matching the specified schema.
     */
    /*public function structured(string $prompt, array $schema): array
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
    }*/


    /**
     * Execute a structured response request enforcing strict compliance with a given JSON schema.
     *
     * @param string $prompt The user prompt instructions.
     * @param array<string, mixed> $schema The JSON Schema array specifying the required keys and types.
     *
     * @throws RuntimeException If all retry attempts exhaust without generating a valid schema-compliant response.
     * @return array<string, mixed> The decoded associative array matching the specified schema.
     */
    public function structured(string $prompt, array $schema): array
    {
        $system = "You must return ONLY valid JSON matching this schema:\n"
            . json_encode($schema, JSON_PRETTY_PRINT);

        return $this->structuredWithMessages([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $prompt],
        ], $schema);
    }


    /**
     * Execute a structured request using pre-built chat messages.
     *
     * Attempts native JSON Schema output first. If the native response cannot be
     * decoded or fails schema validation, it falls back to a maximum of three
     * self-correction attempts while preserving the supplied message context.
     *
     * @param array<int, array{role: string, content: string}> $messages Chat messages to send to the AI model.
     * @param array<string, mixed> $schema The JSON Schema array specifying the required structure.
     *
     * @throws RuntimeException If all retry attempts exhaust without generating a valid schema-compliant response.
     * @return array<string, mixed> The decoded associative array matching the specified schema.
     */
    private function structuredWithMessages(array $messages, array $schema): array
    {
        $maxAttempts = 3;

        $nativeOptions = [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'structured_response',
                    'schema' => $schema,
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

                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response,
                ];

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


    /**
     * Execute a dynamic prompt template with the supplied runtime variables.
     *
     * The template is rendered into concrete system and user messages. Prompts that
     * define a JSON schema are executed through the existing structured response
     * flow; prompts without a schema are executed as standard chat requests.
     *
     * @param PromptTemplate $prompt The prompt template to execute.
     * @param array<string, mixed> $variables Values used to replace prompt placeholders.
     *
     * @return array<string, mixed>|string A structured array or a plain text response.
     */
    public function executePrompt(PromptTemplate $prompt, array $variables = []): array|string
    {
        $messages = $this->promptRenderer->render($prompt, $variables);

        $schema = $prompt->getSchema();

        if ($schema !== null) {
            return $this->structuredWithMessages($messages,$schema);
        }

        return $this->chat($messages);
    }
}
