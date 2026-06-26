<?php
namespace App\AI\Schema;

class SummarySchema
{
    public static function schema(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "summary" => ["type" => "string"],
                "topics" => [
                    "type" => "array",
                    "items" => ["type" => "string"]
                ]
            ],
            "required" => ["summary"]
        ];
    }
}
