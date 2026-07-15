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


    public function test_it_allows_nullable_field_with_null_value(): void
    {
        $schema = [
            'properties' => [
                'nullable_field' => [
                    'type' => 'string',
                    'nullable' => true
                ]
            ],
        ];
        $data = ['nullable_field' => null];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_rejects_null_for_non_nullable_field(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Field 'non_nullable_field' cannot be null.");

        $schema = [
            'properties' => [
                'non_nullable_field' => [
                    'type' => 'string'
                ]
            ]
        ];
        $data = ['non_nullable_field' => null];

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_allows_nullable_type_array_syntax(): void
    {
        $schema = [
            'properties' => [
                'nullable_field' => [
                    'type' => ['string', 'null']
                ]
            ]
        ];

        //First case: null value is accepted
        $dataWithNull = ['nullable_field' => null];
        $this->assertTrue(StructuredResponseValidator::validate($dataWithNull, $schema));

        //Second case: Non-null value with valid type is also accepted
        $dataWithNull = ['nullable_field' => 'hello'];
        $this->assertTrue(StructuredResponseValidator::validate($dataWithNull, $schema));
    }


    public function test_it_allows_nullable_true_syntax(): void
    {
        $schema = [
            'properties' => [
                'nullable_field' => [
                    'type' => 'string',
                    'nullable' => true
                ]
            ]
        ];
        $data = ['nullable_field' => null];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_validates_nullable_array_items(): void
    {
        $schema = [
            'properties' => [
                'nullable_array' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'nullable' => true
                    ]
                ]
            ]
        ];
        $data = ['nullable_array' => [null, 'value1', null, 'value2']];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_validates_nullable_nested_object(): void
    {
        $schema = [
            'properties' => [
                'nested' => [
                    'type' => 'object',
                    'nullable' => true,
                    'properties' => [
                        'field' => ['type' => 'string']
                    ]
                ]
            ]
        ];

        //First case: the entire nested object has a null value.
        $dataNull = ['nested' => null];
        $this->assertTrue(StructuredResponseValidator::validate($dataNull, $schema));

        //Second case: The nested object has a value and its value is valid.
        $dataNotNull = ['nested' => ['field' => 'value']];
        $this->assertTrue(StructuredResponseValidator::validate($dataNotNull, $schema));
    }


    public function test_it_still_validates_non_null_nullable_field_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Field 'nullable_field' must be of type 'string', 'integer' given.");

        $schema = [
            'properties' => [
                'nullable_field' => [
                    'type' => 'string',
                    'nullable' => true
                ]
            ]
        ];
        $data = ['nullable_field' => 123]; //Non-null value with invalid type (integer instead of string)

        StructuredResponseValidator::validate($data, $schema);
    }

    public function test_it_rejects_invalid_type_for_nullable_array_items(): void
    {
        $schema = [
            'properties' => [
                'nullable_array' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'nullable' => true,
                    ],
                ]
            ],
        ];

        $data = ['nullable_array' => [null, 'ok', 123]];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Field 'nullable_array.2' must be of type 'string', 'integer' given.");

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_validates_nullable_enum(): void
    {
        $schema = [
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['pending', 'approved'],
                    'nullable' => true
                ]
            ]
        ];

        //First case: null value is allowed
        $dataNull = ['status' => null];
        $this->assertTrue(StructuredResponseValidator::validate($dataNull, $schema));

        //Second case: valid enum value is allowed
        $dataValid = ['status' => 'approved'];
        $this->assertTrue(StructuredResponseValidator::validate($dataValid, $schema));
        //Case 3: Invalid value should be rejected
    }


    public function test_it_rejects_invalid_enum_for_nullable_field(): void
    {
        $schema = [
            'properties' => [
                'status' => [
                    'type' => 'string',
                    'enum' => ['pending', 'approved'],
                    'nullable' => true
                ]
            ]
        ];

        $dataInvalid = ['status' => 'rejected'];

        $this->expectException(RuntimeException::class);

        $this->expectExceptionMessage("Field 'status' must be one of ['pending', 'approved'], ''rejected'' given.");

        StructuredResponseValidator::validate($dataInvalid, $schema);
    }


    public function test_it_validates_nullable_array_of_objects(): void
    {
        $schema = [
            'properties' => [
                'users' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'nullable' => true,
                        'required' => ['name'],
                        'properties' => [
                            'name' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ];

        //Success case: The array contains one valid object and one null object.
        $data = [
            'users' => [
                ['name' => 'Ali'],
                null
            ]
        ];

        $this->assertTrue(StructuredResponseValidator::validate($data, $schema));
    }


    public function test_it_rejects_string_shortet_than_min_length(): void
    {
        $schema = [
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'minLength' => 5,
                ],
            ],
        ];

        $data = ['title' => 'PHP'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'title' must have a minimum length of 5."
        );

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_rejects_string_longer_than_max_length(): void
    {
        $schema = [
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'maxLength' => 5
                ],
            ],
        ];

        $data = ['title' => 'Laravel'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'title' must have a maximum length of 5."
        );

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_allows_string_at_min_and_max_length_boundaries(): void
    {
        $schema=[
            'properties'=>[
                'code'=>[
                    'type'=>'string',
                    'minLength'=>3,
                    'maxLength'=>3
                ],
            ],
        ];

        $this->assertTrue(StructuredResponseValidator::validate(['code'=>'PHP'],$schema));
    }


    public function test_it_rejects_number_below_minimum(): void
    {
        $schema = [
            'properties' => [
                'score' => [
                    'type' => 'number',
                    'minimum' => 0
                ],
            ],
        ];

        $data = ['score' => -0.5];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'score' must be greater than or equal to 0."
        );

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_rejects_number_above_maximum(): void
    {
        $schema = [
            'properties' => [
                'score' => [
                    'type' => 'number',
                    'maximum' => 1
                ],
            ],
        ];

        $data = ['score' => 1.5];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'score' must be less than or equal to 1."
        );

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_rejects_array_with_fewer_than_min_items(): void
    {
        $schema = [
            'properties' => [
                'topics' => [
                    'type' => 'array',
                    'minItems' => 2,
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        $data = ['topics' => ['php']];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'topics' must contain at least 2 items."
        );

        StructuredResponseValidator::validate($data, $schema);
    }


    public function test_it_rejects_array_with_more_than_max_items(): void
    {
        $schema = [
            'properties' => [
                'topics' => [
                    'type' => 'array',
                    'maxItems' => 2,
                    'items' => ['type' => 'string'],
                ],
            ],
        ];

        $data = ['topics' => ['php', 'laravel', 'openai']];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Field 'topics' must contain at most 2 items."
        );

        StructuredResponseValidator::validate($data, $schema);
    }
}
