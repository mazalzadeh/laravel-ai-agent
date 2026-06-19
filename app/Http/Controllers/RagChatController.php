<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RagChatRequest;
use App\Services\AIService;
use App\Services\VectorSimilarityService;

class RagChatController extends Controller
{
    public function __construct(
        private AIService $ai,
        private VectorSimilarityService $simililarity
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(RagChatRequest $request)
    {
        $question = $request->question;
        $limit = $request->limit ?? 5;

        $embedding = $this->ai->embed($question);

        $results = $this->simililarity->findMostSimilar($embedding, $limit);

        $context = collect($results)->pluck('document.content')->implode("\n\n---\n\n");

        $messages = [
            [
                'role' => 'system',
                'content' => 'Answer using the provided context only.'
            ],
            [
                'role' => 'user',
                'content' => "Context:\n{$context}\n\nQuestion:\n{$question}"
            ]
        ];

        $answer = $this->ai->chat($messages);

        return response()->json([
            'question' => $question,
            'answer' => $answer,
            'sources' => collect($results)->map(fn($r) => [
                'content' => $r['document']->content,
                'score' => $r['score'],
            ]),
        ]);
    }
}
