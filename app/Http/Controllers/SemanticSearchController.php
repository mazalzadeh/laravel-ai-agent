<?php

namespace App\Http\Controllers;

use App\Http\Requests\SemanticSearchRequest;
use App\Services\AIService;
use App\Services\VectorSimilarityService;

class SemanticSearchController extends Controller
{
    /**
     * Handle the semantic search request, generate a query embedding, and return the most similar documents as JSON.
     *
     * Reads the search query and optional result limit from the request, creates an
     * embedding vector for the query, retrieves the most similar documents through
     * the similarity service, and formats the response payload for the client.
     *
     * @param SemanticSearchRequest $request The incoming request containing the search query and optional limit.
     * @param AIService $ai The AI service used to generate the query embedding.
     * @param VectorSimilarityService $similarity The service used to find documents with the highest vector similarity.
     *
     * @return \Illuminate\Http\JsonResponse The JSON response containing the original query and matched results.
     */
    public function __invoke(
        SemanticSearchRequest $request,
        AIService $ai,
        VectorSimilarityService $similarity
    ) {
        $query = $request->input('query');
        $limit = $request->input('limit', 5);

        //generate embedding
        $embedding = $ai->embed($query);

        //search similar documents
        $results = $similarity->findMostSimilar($embedding, $limit);

        return response()->json([
            'query' => $query,
            'results' => $results->map(function ($item) {
                return [
                    'content' => $item['document']->content,
                    'score' => $item['score']
                ];
            })
        ]);
    }
}
