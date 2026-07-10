<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\AI\StructuredResponseValidator;
use RuntimeException;

class StructuredResponseValidatorTest extends TestCase
{
    public function test_it_validates_required_fields()
    {
        $schema = ['required' => ['answer', 'confidence']];
        $data = ['answer' => 'Help'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('required field: confidence');
        $this->expectExceptionMessage("AI response is missing required field: confidence");

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_validates_data_types()
    {
        $schema = [
            'properties' => [
                'confidence' => ['type' => 'number']
            ]
        ];
        $data = ['confidence' => 'very high'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Field 'confidence' must be of type 'number', 'string' given.");

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_passes_valid_data()
    {
        $schema = [
            'required' => ['id'],
            'properties' => ['id' => ['type' => 'integer']]
        ];
        $data = ['id' => 123];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_validates_array_item_types(): void
    {
        $schema = ['properties' => ['tags' => ['type' => 'array', 'items' => ['type' => 'string'],],],];

        $data = ['tags' => ['php', 123, 'laravel'],];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Field 'tags.1' must be of type 'string', 'integer' given.");


        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_passes_valid_array_items(): void
    {
        $schema = ['properties' => ['tags' => ['type' => 'array', 'items' => ['type' => 'string'],],],];

        $data = ['tags' => ['php', 'laravel', 'openai'],];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_validates_array_of_objects(): void
    {
        $schema = ['properties' => ['users' => ['type' => 'array', 'items' => ['type' => 'object', 'required' => ['name'], 'properties' => ['name' => ['type' => 'string'],],],],],];

        $data = ['users' => [['name' => 'Ali'], [],],];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI response is missing required field: users.1.name');

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_validates_enum_values(): void
    {
        $schema = [
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => [
                        'pending',
                        'approved',
                        'rejected',
                    ],
                ],
            ],
        ];

        $data = [
            'status' => 'archived',
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'status' must be one of ['pending', 'approved', 'rejected'], ''archived'' given."
        );

        StructuredResponseValidator::validate($data, $schema);
    }



    public function test_it_passes_valid_enum_values(): void
    {
        $schema = ['properties' => ['status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected'],],],];

        $data = ['status' => 'approved'];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_validates_enum_values_in_array_items(): void
    {
        $schema = ['properties' => ['statuses' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected'],],],],];

        $data = ['statuses' => ['pending', 'unknown'],];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'statuses.1' must be one of ['pending', 'approved', 'rejected'], ''unknown'' given."
        );

        StructuredResponseValidator::validate($data, $schema);
    }
}
