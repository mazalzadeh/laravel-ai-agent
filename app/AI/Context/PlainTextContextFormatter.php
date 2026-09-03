<?php

declare(strict_types=1);

namespace App\AI\Context;

use Illuminate\Support\Collection;

final class PlainTextContextFormatter implements ContextFormatter
{
    /**
     * @param Collection<int, RetrievedDocument> $documents
     */
    public function format(Collection $documents): string
    {
        return $documents
            ->map(
                static fn(RetrievedDocument $document): string => $document->content
            )->implode("\n\n");
    }
}
