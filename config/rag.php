<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retrieval & Context Settings (Legacy & Direct Keys)
    |--------------------------------------------------------------------------
    */
    'max_documents' => (int) env('RAG_MAX_DOCUMENTS', 5),
    'min_score' => (float) env('RAG_MIN_SCORE', 0.7),
    'max_context_length' => (int) env('RAG_MAX_CONTEXT_LENGTH', 4000),
    'max_context_tokens' => (int) env('RAG_MAX_CONTEXT_TOKENS', 1200),
    'seperator' => "\n\n---\n\n",
    'show_scores' => (bool) env('RAG_SHOW_SCORES', true),
    'show_doc_ids' => (bool) env('RAG_SHOW_DOC_IDS', true),
    'empty_fallback' => env('RAG_EMPTY_FALLBACK', 'No relevant context found.'),

    /*
    |--------------------------------------------------------------------------
    | Chunking Settings
    |--------------------------------------------------------------------------
    */
    'chunk_size' => (int) env('RAG_CHUNK_SIZE', 1000),
    'chunk_overlap' => (int) env('RAG_CHUNK_OVERLAP', 200),

    /*
    |--------------------------------------------------------------------------
    | Default Prompts
    |--------------------------------------------------------------------------
    */
    'prompts' => [
        'default' => \App\AI\Prompts\Templates\RagPrompt::class,
    ],
];

/*return [
    'max_documents' => 5,
    'max_context_length' => 4000,
    'max_context_tokens' => 1200,
    'seperator' => "\n\n---\n\n",
    'show_scores' => true,
    'show_doc_ids' => true,

    'chunk_size' => 1000,
    'chunk_overlap' => 200,
];*/
