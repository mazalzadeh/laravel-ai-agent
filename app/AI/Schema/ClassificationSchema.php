<?php

namespace App\AI\Schema;

class ClassificationSchema
{
    public static function toArray(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "category" => ["type" => "string", "enum" => ["support", "billing", "technical", "other"]],
                "priority" => ["type" => "string", "enum" => ["low", "medium", "high"]]
            ],
            "required" => ["category", "priority"]
        ];
    }
}
