<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(protected AIService $aiservice){}

    public function chat(Request $request, AIService $ai)
    {
        $validated = $request->validate(['message' => ['required', 'string'],]);

        $messages = [['role' => 'user', 'content' => $validated['message'],]];

        $reply = $ai->chat($messages);

        return response()->json(['reply' => $reply]);
    }

    public function stream(): StreamedResponse
    {
        $messages = $request->input('messages');

        $stream = $this->aiservice->streamChat($messages);

        return response()->stream(function () use ($stream) {
            foreach ($stream as $chunk) {
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            echo "data: [DONE]\n\n";

            if (ob_get_level() > 0) {
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
