<?php
namespace App\Clients;

use App\DTO\ChatResponseDTO;
use App\DTO\OpenAIErrorDTO;
use App\Services\AIClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

class OpenAIClient implements AIClientInterface
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? '';
    }

    public function chat(array $messages, array $options = []): array
    {
        try {
            $model = $options['model'] ?? 'gpt-4o';

            $allowedOptions=[
                'temperature',
                'max_tokens',
                'top_p',
                'presence_penalty',
                'frequency_penalty',
                'response_format',
            ];

            $filteredOptions = array_intersect_key($options, array_flip($allowedOptions));

            $payload = array_merge(['model' => $model, 'messages' => $messages], $filteredOptions);

            $response = Http::withToken($this->apiKey)
                ->retry(3, 200, function ($exception, $request) {
                    return $this->shouldRetry($exception);
                }, throw: false)
                ->post(
                    'https://api.openai.com/v1/chat/completions',
                    $payload
                );

            if ($response->successful() && $this->extractErrorType($response) === null) {
                return [
                    'success' => true,
                    'data' => ChatResponseDTO::fromArray($response->json())
                ];
            } else {
                return ['success' => false, 'error' => $this->handleError($response)];
            }
        } catch (ConnectionException $e) {
            Log::error('OpenAI Connection Error: ' . $e->getMessage());
            return ['success' => false, 'error' => new OpenAIErrorDTO('connection_error', $e->getMessage(), '0')];
        }
    }

    private function extractErrorType(Response $response): ?string
    {
        if (!$response->successful()) {
            return $response->json()['error']['type'] ?? $response->json()['error']['message'] ?? 'api_error';
        }

        $body = $response->json();
        if (isset($body['error']['type'])) {
            return $body['error']['type'];
        }
        if (isset($body['error']['message'])) {
            return 'api_error';
        }
        return null;
    }

    private function shouldRetry($exception): bool
    {
        //Retry on network errors
        if ($exception instanceof ConnectionException) {
            return true;
        }

        //Retry on specific Http status codes
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            $status = $exception->response->status();
            return in_array($status, [429, 500, 502, 503, 504]);
        }

        return false;
    }

    private function handleError(Response $response): OpenAIErrorDTO
    {
        $status = $response->status();
        $body = $response->json();

        $message = $body['error']['message'] ?? ($body['error'] ?? 'Unknown error');
        if (is_array($message)) {
            $message = json_encode($message);
        }

        $type = match ($status) {
            429 => 'rate_limit_exceeded',
            401 => 'invalid_api_key',
            400 => 'bad_request',
            default => 'api_error',
        };

        if (!$response->successful() && isset($body['error']['type'])) {
            $type = $body['error']['type'];
        }

        Log::warning("OpenAI API Error [$status]: $message");

        return new OpenAIErrorDTO($type, $message, (string)$status);
    }
}