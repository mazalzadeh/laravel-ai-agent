<?php

declare(strict_types=1);

namespace App\AI\Context;

use Illuminate\Support\Collection;

interface ContextFormatter
{
    /**
     * @param Collection<int, RetrievedDocument> $documents
     */
    public function format(Collection $documents): string;
}
