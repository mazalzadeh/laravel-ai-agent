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
            $expectedTypes = self::getExpectedTypes($propertySchema);
            $fieldPath = self::buildPath($path, $key);

            if ($value === null) {
                if (self::isNullable($propertySchema)) {
                    continue;
                }

                throw new RuntimeException("Field '{$fieldPath}' cannot be null.");
            }

            if ($expectedTypes !== [] && !self::matchesAnyType($value, $expectedTypes)) {
                $actualType = self::getActualType($value);
                $expectedTypesText = implode('|', $expectedTypes);

                throw new RuntimeException(
                    "Field '{$fieldPath}' must be of type " .
                        "'{$expectedTypesText}', '{$actualType}' given."
                );
            }

            self::validateEnum($value, $propertySchema, $fieldPath);

            //Recursively validate nested objects
            if (in_array('object', $expectedTypes, true)) {
                self::validateSchema(
                    $value,
                    $propertySchema,
                    $fieldPath
                );
            }

            if (in_array('array', $expectedTypes, true)) {
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

        if ($value === null && self::isNullable($schema)) {
            return;
        }

        if (
            !array_key_exists('enum', $schema)
            || !is_array($schema['enum'])
        ) {
            return;
        }

        if (!in_array($value, $schema['enum'], true)) {
            $allowedValues = implode(
                ', ',
                array_map(
                    static fn($item) => var_export($item, true),
                    $schema['enum']
                )
            );


            $actualValue = var_export($value, true);

            throw new RuntimeException(
                "Field '{$path}' must be one of " .
                    "[{$allowedValues}], '{$actualValue}' given."
            );
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
            $expectedItemTypes = self::getExpectedTypes($itemSchema);

            /*
            * Handle nullable array items before normal type validation.
            */
            if ($item === null) {
                if (self::isNullable($itemSchema)) {
                    continue;
                }

                throw new RuntimeException(
                        "Field '{$itemPath}' cannot be null."
                );
            }

            /*
            * Validate non-null item types.
            */
            if ($expectedItemTypes !== [] && !self::matchesAnyType($item, $expectedItemTypes)) {
                $actualType = self::getActualType($item);
                $expectedItemTypesText = implode('|', $expectedItemTypes);

                throw new RuntimeException(
                    "Field '{$itemPath}' must be of type " .
                        "'{$expectedItemTypesText}', '{$actualType}' given."
                );
            }


            self::validateEnum($item, $itemSchema, $itemPath);

            /*
            * Recursively validate object items.
            */
            if (in_array('object', $expectedItemTypes, true)) {
                self::validateSchema($item, $itemSchema, $itemPath);
            }

            /*
            * Recursively validate nested array items.
            */
            if (in_array('array', $expectedItemTypes, true)) {
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

    //Compares the value against all allowed types. If it matches at least one of them, it returns true.
    private static function matchesAnyType(mixed $value, array $expectedTypes): bool
    {
        foreach ($expectedTypes as $expectedType) {
            if (self::matchesType($value, $expectedType)) {
                return true;
            }
        }

        return false;
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


    private static function isNullable(array $schema): bool
    {
        if (($schema['nullable'] ?? false) === true) {
            return true;
        }

        $type = $schema['type'] ?? null;

        if (is_array($type) && in_array('null', $type, true)) {
            return true;
        }

        return false;
    }


    private static function getExpectedTypes(array $schema): array
    {
        $type = $schema['type'] ?? null;

        if ($type === null) {
            return [];
        }

        if (is_array($type)) {
            return array_values(array_filter($type, static fn(mixed $item): bool => is_string($item) && $item !== 'null'));
        }

        return is_string($type) ? [$type] : [];
    }
}
