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

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? '';
        $this->baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $this->model = config('services.openai.model', 'gpt-4o');
    }



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



    private function extractErrorType(Response $response): ?string
    {
        $json = $response->json();

        return $json['error']['type'] ?? null;
    }

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


    private function handleError(Response $response): OpenAIErrorDTO
    {
        $json = $response->json();

        $error = $json['error'] ?? [];

        return OpenAIErrorDTO::fromArray(
            data: $response->json() ?? [],
            status: $response->status()
        );
    }


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
