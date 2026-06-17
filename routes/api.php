<?php

use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SemanticSearchController;

Route::post('/fake-openai/chat', function(Request $request){

    $message = $request->input('messages.0.content');

    return response()->json(
        [
            'id' => 'chatcmpl-fake',
            'object' => 'chat.completion',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => "You said: " . $message
                    ],
                    'finish_reason' => 'stop'
                ]
            ]
        ]
    );
});

Route::post('/chat',[ChatController::class, 'chat']);
Route::post('/chat/stream', [ChatController::class, 'stream']);
Route::post('/semantic-search', SemanticSearchController::class);
