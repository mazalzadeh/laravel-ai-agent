<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(protected AIService $aiservice){}

    /**
     * Validate the incoming message, send it to the AI service, and return the reply as JSON.
     *
     * Accepts a user message from the HTTP request, validates it, transforms it
     * into the chat message format expected by the AI service, and returns the
     * service response in a JSON payload.
     *
     * @param Request $request The incoming HTTP request containing the user message.
     * @param AIService $ai The AI service used to generate a chat response.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the AI reply.
     */
    public function chat(Request $request, AIService $ai)
    {
        $validated = $request->validate(['message' => ['required', 'string'],]);

        $messages = [['role' => 'user', 'content' => $validated['message'],]];

        $reply = $ai->chat($messages);

        return response()->json(['reply' => $reply]);
    }

    /**
     * Validate incoming chat messages, stream AI response chunks, and return them as Server-Sent Events.
     *
     * Accepts an array of chat messages, validates the required structure, forwards
     * the messages to the AI streaming service, and emits each returned chunk as an
     * SSE `data:` event. A final `[DONE]` marker is sent when streaming completes.
     *
     * @param Request $request The incoming HTTP request containing the chat messages.
     *
     * @return StreamedResponse The streamed SSE response containing incremental AI output.
     */
    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'messages' => ['required', 'array'],
            'messages.*.role' => ['required', 'string'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $messages = $validated['messages'];

        $stream = $this->aiservice->streamChat($messages);

        return response()->stream(function () use ($stream) {
            foreach ($stream as $chunk) {
                // echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                echo 'data: ' . json_encode(['content' => $chunk], JSON_UNESCAPED_UNICODE) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            echo "data: [DONE]\n\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
