<?php

namespace App\Clients;

use App\DTO\ChatResponseDTO;
use App\DTO\OpenAIErrorDTO;
use App\Services\AIClientInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

class OpenAIClient implements AIClientInterface
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    /**
     * Create a new OpenAI client instance from the application configuration.
     *
     * Loads the API key, base URL, and default model from the configured
     * OpenAI service settings, applying fallback defaults where needed.
     */
    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? '';
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->model = config('services.openai.model', 'gpt-4o');
    }


    /**
     * Send a chat completion request and return either a parsed response or a normalized error.
     *
     * Builds the request payload from the configured model, the provided messages,
     * and any supported options. Retries transient HTTP and connection failures
     * before returning either a `ChatResponseDTO` instance or a normalized
     * `OpenAIErrorDTO`.
     *
     * @param array $message The chat message payload sent to the API.
     * @param array $options Additional request options such as temperature, max tokens, or top_p.
     *
     * @return array A normalized result array containing:
     *               - `success` (bool): Whether the request succeeded.
     *               - `data` (ChatResponseDTO): Present on success.
     *               - `error` (OpenAIErrorDTO): Present on failure.
     */
    public function chat(array $message, array $options = []): array
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $message,
        ], $this->filterOptions($options));

        $maxAttemps = 3;
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::withToken($this->apiKey)
                    ->post($this->baseUrl . '/chat/completions', $payload);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'data' => ChatResponseDTO::fromArray($response->json()),
                    ];
                }

                if ($attempt < $maxAttemps && in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                    usleep(200_000);
                    continue;
                }

                return [
                    'success' => false,
                    'error' => $this->handleError($response),
                ];
            } catch (ConnectionException $e) {
                if ($attempt < $maxAttemps) {
                    usleep(200_000);
                    continue;
                }

                return [
                    'success' => false,
                    'error' => new OpenAIErrorDTO(
                        type: 'connection_error',
                        message: 'Connection to OpenAI failed.',
                        status: null
                    ),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'error' => new OpenAIErrorDTO(
                        type: 'unexpected_error',
                        message: $e->getMessage(),
                        status: null
                    ),
                ];
            }
        }
    }

    /**
     * Send a streaming chat completion request and yield incremental content fragments from the API response.
     *
     * Builds a streaming chat completion payload, sends it to the API, and reads
     * the server-sent event stream line by line. Each non-empty content fragment
     * found in the response delta is yielded as it arrives.
     *
     * @param array $messages The chat messages sent to the API.
     * @param array $options Additional request options such as temperature, max tokens, or top_p.
     *
     * @return Generator<string> A generator that yields streamed content chunks.
     *
     * @throws \RuntimeException If the streaming request fails, the connection is interrupted,
     *                           or an unexpected streaming error occurs.
     */
    public function streamChat(array $messages, array $options = []): Generator
    {
        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'stream' => true,
        ], $this->filterOptions($options));

        try {
            $response = Http::withToken($this->apiKey)
                ->withOptions(['stream' => true])
                ->post($this->baseUrl . '/chat/completions', $payload);

            if (!$response->successful()) {
                $error = $this->handleError($response);

                Log::error('OpenAI streaming request failed', [
                    'message' => $error->message,
                    'type' => $error->type,
                    'status' => $error->status,
                ]);

                throw new \RuntimeException($error->message, $error->code ?? 0);
            }

            $stream = $response->toPsrResponse()->getBody();

            while (!$stream->eof()) {
                $line = $this->readLine($stream);

                if ($line === '') {
                    continue;
                }

                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $data = substr($line, 6);

                if ($data === '[DONE]') {
                    break;
                }

                $decoded = json_decode($data, true);

                if (!is_array($decoded)) {
                    continue;
                }

                $content = $decoded['choices'][0]['delta']['content'] ?? null;

                if ($content !== null && $content !== '') {
                    yield $content;
                }
            }
        } catch (ConnectionException $e) {
            Log::error('OpenAI streaming connection failed', [
                'message' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Connection to OpenAI failed during streaming.', 0, $e);
        } catch (\Throwable $e) {
            Log::error('Unexpected OpenAI streaming error', [
                'message' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Unexpected error while streaming from OpenAI.',
                0,
                $e
            );
        }
    }

    /**
     * Generate an embedding vector for the given text using the OpenAI embeddings API.
     *
     * Sends the provided text to the embeddings endpoint using the configured API
     * token and returns the first embedding vector from the response payload.
     *
     * @param string $text The input text to convert into an embedding vector.
     *
     * @return array The numeric embedding vector returned by the API.
     *
     * @throws \Exception If the embedding request fails.
     */
    public function embed(string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI embedding request failed');
        }

        return $response->json('data.0.embedding');
    }


    /**
     * Extract the error type from the API response payload.
     *
     * Reads the JSON response body and returns the nested error type if it is
     * present in the API error structure.
     *
     * @param Response $response The HTTP response returned by the API.
     *
     * @return string|null The error type when available, otherwise null.
     */
    private function extractErrorType(Response $response): ?string
    {
        $json = $response->json();

        return $json['error']['type'] ?? null;
    }

    /**
     * Read a single trimmed line from the given stream.
     *
     * Consumes the stream one character at a time until a newline character is
     * reached or the end of the stream is encountered, then returns the trimmed
     * line contents.
     *
     * @param mixed $stream The readable stream instance.
     *
     * @return string The trimmed line read from the stream.
     */
    private function readLine($stream): string
    {
        $buffer = '';

        while (!$stream->eof()) {
            $char = $stream->read(1);

            if ($char === "\n") {
                break;
            }

            $buffer .= $char;
        }

        return trim($buffer);
    }


    /**
     * Determine whether the given exception represents a retryable failure.
     *
     * Returns true for network connection failures and for exceptions that expose
     * an HTTP response with a transient status code such as 429 or 5xx retryable
     * server errors.
     *
     * @param mixed $exception The thrown exception or error candidate to inspect.
     *
     * @return bool True if the failure is considered temporary and should be retried.
     */
    private function shouldRetry(mixed $exception): bool
    {
        //Retry on network errors
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (method_exists($exception, 'response') && $exception->response) {
            $status = $exception->response->status();

            return in_array($status, [429, 500, 502, 503, 504], true);
        }
        return false;
    }

    /**
     * Convert an API error response into a normalized OpenAI error DTO.
     *
     * Reads the response payload and wraps the error details in an
     * `OpenAIErrorDTO` instance using the response body and HTTP status code.
     *
     * @param Response $response The failed HTTP response returned by the API.
     *
     * @return OpenAIErrorDTO A normalized error DTO built from the API response.
     */
    private function handleError(Response $response): OpenAIErrorDTO
    {
        $json = $response->json();

        $error = $json['error'] ?? [];

        return OpenAIErrorDTO::fromArray(
            data: $response->json() ?? [],
            status: $response->status()
        );
    }

    /**
     * Filter the given options array to include only supported API parameters.
     *
     * Removes any unsupported or unexpected options and returns only the keys
     * that are explicitly allowed to be sent with the API request payload.
     *
     * @param array $options The raw request options provided by the caller.
     *
     * @return array The filtered options array containing only allowed parameters.
     */
    private function filterOptions(array $options): array
    {
        $allowedOptions = [
            'temperature',
            'top_p',
            'max_tokens',
            'presence_penalty',
            'frequency_penalty',
            'stop',
            'user',
            'response_format',
        ];

        return array_intersect_key($options, array_flip($allowedOptions));
    }
}
