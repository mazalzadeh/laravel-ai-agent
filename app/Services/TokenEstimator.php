<?php

namespace App\Services;

class TokenEstimator
{
    /**
     * Estimate the approximate number of tokens in a text string.
     *
     * This is a lightweight heuristic that assumes roughly one token
     * per four characters. It is useful for rough context budgeting
     * where exact tokenizer accuracy is not required.
     *
     * @param string $text The input text to estimate.
     *
     * @return int The estimated token count.
     */
    public function estimate(string $text): int
    {
        return (int)ceil(strlen($text) / 4);
    }
}
