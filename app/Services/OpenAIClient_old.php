<?php

namespace App\Services;

use App\DTO\ChatResponseDTO;
use App\DTO\OpenAIErrorDTO;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class OpenAIClient implements AIClientInterface
{
    public function chat(string $message) : array
    {
        try{
            $response = Http::withToken(config('services.openai.key'))
                        ->retry(
                            3,
                            function ($attemp){
                                //Exponential Backoff
                                return 200 * (2**($attemp - 1));
                            },
                            function ($exception, $request) {
                                //just on error with retry
                                if ($exception instanceof ConnectionException) {
                                    return true;
                                }

                                if ($exception instanceof RequestException) {
                                    $status = $exception->response?->status();
                                    return in_array($status, [429, 500, 502, 502]);
                                }

                                return false;
                            }
                        )
                        ->timeout(10)
                        ->post(config('services.openai.base_url') . '/chat/completions',[
                            'model' => 'gpt-4o-mini',
                            'messages' => [
                                [ 'role' => 'user', 'content' => $message ]
                            ],
                        ]);
            if ($response->failed()) {
                return $this->handleErrorResponse($response);
            }

            return [
                'success' => true,
                //'data' => $response->json(),
                'data' => ChatResponseDTO::fromArray($response->json(),)
            ];
        } catch (ConnectionException $e) {
            Log::error('OpenAI connection failed', ['message' => $e->getMessage()]);

            return $this->networkError();
        } catch (RequestException $e) {
            return $this->handleException($e);
        }
    }

    private function handleException(RequestException $e): array
    {
        $response = $e->response;

        if(!$response) {
            return $this->networkError();
        }
        
        $error = $response->json('error');

        return [
            'success' => false,
            'error' => OpenAIErrorDTO::fromArray($response->json(), $response->status()),
        ];
    }

    private function networkError(): array 
    {
        return [
            'success' => false,
            'error' => new OpenAIErrorDTO(
                type: 'connection_error',
                message: 'Network error while contacting OpenAI',
                status: 0
            ),
        ];
    }

    /*public function chat(string $message): array
    {
        $baseUrl = rtrim(config('services.openai.base_url'), '/');
        $apiKey = (string) config('services.openai.key');


        try{
            $response = Http::baseUrl($baseUrl)
                        ->withToken($apiKey)
                        ->acceptJson()
                        ->timeout(15)
                        ->post('/chat/completions', [
                            'model' => 'gpt-4.1-mini',  // هر مدل دلخواه؛ در تست اهمیتی ندارد
                            'messages' => [['role' => 'user', 'content' => $message],]
                        ]);
            if($response->successful()){
                return[
                    'success' => true,
                    'data'    => $response->json(),  
                ];
            }

            if($response->failed()){
                return[
                    'success' => false,
                    'error'   => OpenAIErrorDTO::fromArray($response->json(), $response->status())
                ];
            }   
        } catch (\Illuminate\Http\Client\ConnectionException){
            return [
                'success' => false,
                'error'   => new OpenAIErrorDTO(
                    type:'connection_error',
                    message: 'Unable to connect to OpenAI servers.',
                    status: 0
                )
            ];
        }
    }*/

    /*public function chat(string $message) : array
    {
        $response = Http::withToken(config('services.openai.key'))
                        ->post('https://api.openai.com/v1/chat/completions',[
                            'model' => 'gpt-4o-mini',
                            'messages' => [
                                'role' => 'user',
                                'content' => $message
                            ]
                        ]);

        return $response->json();
    }*/
}