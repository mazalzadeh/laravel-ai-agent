<?php

namespace App\Http\Controllers;

use App\Http\Requests\RagChatRequest;
use App\Services\RagService;

class RagChatController extends Controller
{
    public function __construct(
        protected RagService $ragService
    ) {}

    /**
     * Handle the incoming RAG chat request and return the generated answer as JSON.
     *
     * Retrieves the validated question from the request, forwards it to the RAG
     * service, and returns the generated result in a JSON response.
     *
     * @param RagChatRequest $request The incoming request containing the user question.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the RAG result.
     */
    public function __invoke(RagChatRequest $request)
    {
        $result = $this->ragService->answer($request->input('question'));

        return response()->json($result);
    }

}
