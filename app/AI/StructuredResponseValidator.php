<?php

namespace App\AI;

use RuntimeException;

class StructuredResponseValidator
{
    /**
     * Validate the given data against the provided schema.
     *
     * Delegates the actual validation logic to the internal schema validator and
     * returns `true` when the payload satisfies the schema requirements.
     *
     * @param array $data The structured response data to validate.
     * @param array $schema The schema definition used to validate the data.
     *
     * @return bool True when the data is valid.
     *
     * @throws \RuntimeException When the data does not match the schema.
     */
    public static function validate(array $data, array $schema): bool
    {
        self::validateSchema($data, $schema);

        return true;
    }

    /**
     * Recursively validate the given data against the provided schema.
     *
     * Checks required fields, validates property types, applies enum and
     * constraint rules, and recursively validates nested objects and arrays
     * defined in the schema.
     *
     * Fields that are not defined in the schema properties are ignored.
     *
     * @param array $data The structured response data to validate.
     * @param array $schema The schema definition used for validation.
     * @param string $path The current dot-notated path for nested validation.
     *
     * @return void
     *
     * @throws \RuntimeException When required fields are missing or any value
     * violates the schema rules.
     */
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

            self::validateConstraints(
                $value,
                $propertySchema,
                $expectedTypes,
                $fieldPath
            );

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


    /**
     * Validate that the given value matches the schema enum constraints.
     *
     * If the schema defines an `enum` list, the value must match one of the
     * allowed entries using strict comparison. Nullable values are accepted
     * when the schema explicitly allows `null`.
     *
     * @param mixed $value The value to validate.
     * @param array $schema The schema definition that may contain enum rules.
     * @param string $path The dot-notated field path used in validation errors.
     *
     * @return void
     *
     * @throws \RuntimeException When the value is not included in the allowed enum list.
     */
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


    /**
     * Validate each array item against the schema defined in the `items` property.
     *
     * Applies nullable checks, type validation, enum validation, and additional
     * constraints to every item in the array. Object and nested array items are
     * validated recursively using their corresponding item schema.
     *
     * @param array $items The array items to validate.
     * @param array $schema The parent schema containing the `items` definition.
     * @param string $path The dot-notated field path used in validation errors.
     *
     * @return void
     *
     * @throws \RuntimeException When any array item violates the item schema.
     */
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

            self::validateConstraints($item, $itemSchema, $expectedItemTypes, $itemPath);

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


    /**
     * Determine whether the given value matches the expected schema type.
     *
     * Uses PHP type checks for supported schema types. JSON objects are treated as
     * arrays because decoded JSON objects become associative arrays when using
     * `json_decode($json, true)`.
     *
     * @param mixed $value The value to validate.
     * @param string $expectedType The expected schema type.
     *
     * @return bool True when the value matches the expected type.
     */
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

    /**
     * Check whether the given value matches at least one of the expected schema types.
     *
     * Iterates through the allowed schema types and returns `true` as soon as the
     * value matches one of them.
     *
     * @param mixed $value The value to validate.
     * @param array $expectedTypes The list of allowed schema types.
     *
     * @return bool True when the value matches at least one expected type.
     */
    private static function matchesAnyType(mixed $value, array $expectedTypes): bool
    {
        foreach ($expectedTypes as $expectedType) {
            if (self::matchesType($value, $expectedType)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Return the normalized schema-friendly type name for the given value.
     *
     * Converts PHP-specific type names such as `double` and `NULL` into
     * more consistent schema-oriented names for validation error messages.
     *
     * @param mixed $value The value whose actual type should be resolved.
     *
     * @return string The normalized type name.
     */
    private static function getActualType(mixed $value): string
    {
        return match (gettype($value)) {
            'double' => 'number',
            'NULL' => 'null',
            default => gettype($value),
        };
    }

    /**
     * Build the full dot-notated path for a nested schema field.
     *
     * Returns the field name as-is when there is no parent path, otherwise
     * appends it to the parent path using dot notation.
     *
     * @param string $parentPath The current parent path.
     * @param string $field The field name to append.
     *
     * @return string The full dot-notated field path.
     */
    private static function buildPath(
        string $parentPath,
        string $field,
    ): string {
        if ($parentPath === '') {
            return $field;
        }

        return "{$parentPath}.{$field}";
    }

    /**
     * Determine whether the schema allows null values.
     *
     * A field is considered nullable when the schema explicitly sets `nullable`
     * to `true` or includes `null` in its `type` definition.
     *
     * @param array $schema The schema definition to inspect.
     *
     * @return bool True when null values are allowed.
     */
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

    /**
     * Normalize the schema type definition into an array of non-null types.
     *
     * Extracts the types from the schema and filters out `null`, ensuring
     * a consistent array format regardless of whether the schema defines
     * a single type string or an array of types.
     *
     * @param array $schema The schema definition to extract types from.
     *
     * @return string[] An array of expected non-null data types.
     */
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

    /**
     * Validate additional schema constraints for the given value.
     *
     * Applies type-specific rules such as string length limits, numeric
     * minimum and maximum bounds, and array item count constraints based
     * on the expected schema types.
     *
     * @param mixed $value The value to validate.
     * @param array $schema The schema definition containing constraint rules.
     * @param array $expectedTypes The normalized list of expected non-null types.
     * @param string $path The dot-notated field path used in validation errors.
     *
     * @return void
     *
     * @throws \RuntimeException When the value violates any supported constraint.
     */
    private static function validateConstraints(
        mixed $value,
        array $schema,
        array $expectedTypes,
        string $path,
    ): void {

        //String constraints
        if (in_array('string', $expectedTypes, true)) {
            $length = mb_strlen($value);

            if (
                array_key_exists('minLength', $schema)
                && $length < $schema['minLength']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must have a minimum length of {$schema['minLength']}."
                );
            }

            if (
                array_key_exists('maxLength', $schema)
                && $length > $schema['maxLength']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must have a maximum length of {$schema['maxLength']}."
                );
            }
        }

        //Numeric constraints
        if (
            in_array('integer', $expectedTypes, true)
            || in_array('number', $expectedTypes, true)
        ) {
            if (
                array_key_exists('minimum', $schema)
                && $value < $schema['minimum']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must be greater than or equal to {$schema['minimum']}."
                );
            }

            if (
                array_key_exists('maximum', $schema)
                && $value > $schema['maximum']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must be less than or equal to {$schema['maximum']}."
                );
            }
        }

        //Array constraints
        if (in_array('array', $expectedTypes, true)) {
            $itemCount = count($value);

            if (
                array_key_exists('minItems', $schema)
                && $itemCount < $schema['minItems']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must contain at least {$schema['minItems']} items."
                );
            }

            if (
                array_key_exists('maxItems', $schema)
                && $itemCount > $schema['maxItems']
            ) {
                throw new RuntimeException(
                    "Field '{$path}' must contain at most {$schema['maxItems']} items."
                );
            }
        }
    }
}
