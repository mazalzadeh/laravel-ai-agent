<?php

namespace App\AI\Schema;

class ClassificationSchema
{

/**
 * Return the JSON schema definition for classification responses.
 *
 * The schema enforces an object with the required `category` and `priority`
 * fields and restricts both values to predefined enums.
 *
 * @return array The classification schema as an associative array.
 */
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
