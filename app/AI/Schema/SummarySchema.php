<?php
namespace App\AI\Schema;

class SummarySchema
{
    /**
     * Return the JSON schema definition for summary responses.
     *
     * The schema requires a `summary` field and optionally allows a `topics`
     * array containing short topic labels related to the generated summary.
     *
     * @return array The summary schema as an associative array.
     */
    public static function schema(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "summary" => [
                    "type" => "string",
                    "minLength" => 20,
                    "maxLength" => 2000,
                ],
                "topics" => [
                    "type" => "array",
                    "minItems" => 1,
                    'maxItems' => 10,
                    "items" => [
                        "type" => "string",
                        "minLength" => 2,
                        "maxLength" => 100,
                    ],
                ],
            ],
            "required" => ["summary"]
        ];
    }
}
