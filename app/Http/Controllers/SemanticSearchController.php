<?php

namespace App\Http\Controllers;

use App\Http\Requests\SemanticSearchRequest as RequestsSemanticSearchRequest;
use Illuminate\Http\Request;
use Iluminate\Http\Request\SemanticSearchRequest;
use App\Services\AIService;
use App\Services\VectorSimilarityService;

class SemanticSearchController extends Controller
{
    public function __invoke(
        RequestsSemanticSearchRequest $request,
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
