<?php

namespace App\Services;

use App\AI\Schema\ClassificationSchema;
use App\Services\AIService;
use App\AI\Schema\SummarySchema;

class DocumentAnalysisService
{
    public function __construct(
        private AIService $ai
    ) {}

    public function summarize(string $text): array
    {
        $prompt = "Summarize this document and extract topics:\n\n" . $text;


        return $this->ai->structured($prompt, SummarySchema::schema());
    }

    public function classify(string $text): array
    {
        return $this->ai->structured(
            "Classify this support ticket:" . $text,
            ClassificationSchema::toArray()
        );
    }

    public function extractEntities(string $text): array
    {
        $schema = [
            "type" => "object",
            "properties" => [
                "person" => ["type" => "string"],
                "amount" => ["type" => "number"],
                "currency" => ["type" => "string"]
            ],
            "required" => ["person", "amount"]
        ];

        return $this->ai->structured("Extract payment details from: " . $text, $schema);
    }
}
