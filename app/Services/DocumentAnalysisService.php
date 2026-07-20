<?php

namespace App\Services;

use App\AI\Schema\ClassificationSchema;
use App\Services\AIService;
use App\AI\Schema\SummarySchema;

class DocumentAnalysisService
{
    /**
     * Create a new document analysis service instance.
     *
     * @param AIService $ai The AI service used for structured text analysis.
     */
    public function __construct(
        private AIService $ai
    ) {}

    /**
     * Summarize a document and extract its main topics.
     *
     * @param string $text The input document text.
     *
     * @return array The structured summary result.
     */
    public function summarize(string $text): array
    {
        $prompt = "Summarize this document and extract topics:\n\n" . $text;


        return $this->ai->structured($prompt, SummarySchema::schema());
    }

    /**
     * Classify a support ticket or similar text.
     *
     * @param string $text The input text to classify.
     *
     * @return array The structured classification result.
     */
    public function classify(string $text): array
    {
        return $this->ai->structured(
            "Classify this support ticket:" . $text,
            ClassificationSchema::toArray()
        );
    }

    /**
     * Extract payment-related entities from the given text.
     *
     * @param string $text The input text containing payment details.
     *
     * @return array The structured entity extraction result.
     */
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
