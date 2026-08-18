<?php

declare(strict_types=1);

namespace App\AI\Context;

use Illuminate\Support\Collection;

interface ContextRetriever
{
    /**
     * Retrieve the most relevant document chunks for a query.
     *
     * @return Collection<int, RetrievedDocument>
     */
    public function retrieve(string $query, int $limit = 5): Collection;
}
