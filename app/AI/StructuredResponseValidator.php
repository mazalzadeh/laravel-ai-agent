<?php

namespace App\AI;

use RuntimeException;

class StructuredResponseValidator
{
    public static function validate(array $data, array $schema): bool
    {
        self::validateSchema($data, $schema);

        return true;
    }


    private static function validateSchema(
        array $data,
        array $schema,
        string $path = ''
    ): void {
        //Check required fields
        foreach ($schema['required'] ?? [] as $field) {
            if (!array_key_exists($field, $data)) {
                $fieldPath = self::buildPath($path, $field);

                throw new RuntimeException("AI response is missing required field: {$fieldPath}");
            }
        }
        //Check properties and their data types
        $properties = $schema['properties'] ?? [];

        foreach ($data as $key => $value) {
            // Ignore fields that are not defined in the schema
            if (!isset($properties[$key])) {
                continue;
            }

            $propertySchema = $properties[$key];
            $expectedType = $propertySchema['type'] ?? null;
            $fieldPath = self::buildPath($path, $key);

            if (
                $expectedType !== null
                && !self::matchesType($value, $expectedType)
            ) {
                $actualType = self::getActualType($value);

                throw new \RuntimeException(
                    "Field '{$fieldPath}' must be of type " .
                        "'{$expectedType}', '{$actualType}' given."
                );
            }

            self::validateEnum($value, $propertySchema, $fieldPath);

            //Recursively validate nested objects
            if ($expectedType === 'object') {
                self::validateSchema(
                    $value,
                    $propertySchema,
                    $fieldPath
                );
            }

            if ($expectedType === 'array') {
                self::validateArrayItems(
                    $value,
                    $propertySchema,
                    $fieldPath
                );
            }
        }
    }

    private static function validateEnum(
        mixed $value,
        array $schema,
        string $path
    ): void {
        if (!array_key_exists('enum', $schema)) {
            return;
        }

        if (!in_array($value, $schema['enum'], true)) {
            $allowedValues = implode(', ', array_map(static fn($item) => var_export($item, true), $schema['enum']));

            $actualValue = var_export($value, true);

            throw new RuntimeException("Field '{$path}' must be one of [{$allowedValues}], '{$actualValue}' given.");
        }
    }


    private static function validateArrayItems(
        array $items,
        array $schema,
        string $path
    ): void {
        if (!isset($schema['items']) || !is_array($schema['items'])) {
            return;
        }

        $itemSchema = $schema['items'];

        foreach ($items as $index => $item) {
            $itemPath = "{$path}.{$index}";
            $expectedItemType = $itemSchema['type'] ?? null;

            if ($expectedItemType !== null && !self::matchesType($item, $expectedItemType)) {
                $actualType = self::getActualType($item);

                throw new RuntimeException(
                    "Field '{$itemPath}' must be of type '{$expectedItemType}', '{$actualType}' given."
                );
            }

            self::validateEnum($item, $itemSchema, $itemPath);

            if ($expectedItemType === 'object') {
                self::validateSchema($item, $itemSchema, $itemPath);
            }

            if ($expectedItemType === 'array') {
                self::validateArrayItems($item, $itemSchema, $itemPath);
            }
        }
    }


    private static function matchesType(
        mixed $value,
        string $expectedType
    ): bool {
        return match ($expectedType) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),

            /*
             * When JSON is decoded using json_decode($json, true),
             * both objects and arrays are converted to PHP arrays.
             */
            'object' => is_array($value),
            'array' => is_array($value),

            // Unsupported schema types are ignored for now
            default => true,
        };
    }


    private static function getActualType(mixed $value): string
    {
        return match (gettype($value)) {
            'double' => 'number',
            'NULL' => 'null',
            default => gettype($value),
        };
    }


    private static function buildPath(
        string $parentPath,
        string $field,
    ): string {
        if ($parentPath === '') {
            return $field;
        }

        return "{$parentPath}.{$field}";
    }
}
