<?php
namespace App\AI\Schema;

class SummarySchema
{
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
