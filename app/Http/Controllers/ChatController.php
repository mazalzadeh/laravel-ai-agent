<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIService;

class ChatController extends Controller
{
    public function chat(Request $request, AIService $ai)
    {
        $validated = $request->validate(['message' => ['required', 'string'],]);

        $messages = [['role' => 'user', 'content' => $validated['message'],]];

        $reply = $ai->chat($messages);

        return response()->json(['reply' => $reply]);
    }
}
