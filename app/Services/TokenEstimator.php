<?php

namespace App\Services;

class TokenEstimator
{
    public function estimate(string $text): int
    {
        return (int)ceil(strlen($text) / 4);
    }
}
