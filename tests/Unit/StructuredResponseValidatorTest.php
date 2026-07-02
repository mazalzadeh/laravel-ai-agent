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
}
