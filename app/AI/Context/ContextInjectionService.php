<?php

declare(strict_types=1);

namespace App\AI\Context;

final class ContextInjectionService
{
    public function __construct(
        private readonly ContextRetriever $retriever,
        private readonly ContextFormatter $formatter,
    ) {}


    public function inject(string $query, int $limit = 5): RetrievedContext
    {
        $documents = $this->retriever->retrieve($query, $limit);

        $formattedText = $this->formatter->format($documents);

        return new RetrievedContext(content: $formattedText, documents: $documents);
    }
}
