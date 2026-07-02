<?php

namespace App\AI;

use InvalidArgumentException;

class StructuredResponseValidator
{
    public static function validate(array $data, array $schema): bool
    {
        // check existing required field
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!array_key_exists($field, $data)) {
                    throw new \RuntimeException("AI response is missing required field: {$field}");
                }
            }
        }

        // checking the correctness of the data type
        $properties = $schema['properties'] ?? [];
        foreach ($data as $key => $value) {
            if (isset($properties[$key])) {
                $expectedType = $properties[$key]['type'] ?? null;
                $actualType = gettype($value);

                $actualType = match ($actualType) {
                    'integer' => 'integer',
                    'double' => 'number',
                    'boolean' => 'boolean',
                    'array' => 'array',
                    default => 'string',
                };

                if ($expectedType === 'number' && $actualType === 'integer') {
                    continue;
                }

                if ($expectedType && $actualType !== $expectedType) {
                    throw new \RuntimeException("Field '{$key}' must be of type '{$expectedType}', '{$actualType}' given.");
                }
            }
        }

        return true;
    }
}
