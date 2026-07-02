<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\AIService;
use Mockery;

class AIServiceTest extends TestCase
{
    public function test_structured_method_returns_validated_data()
    {
        //Mocking AIService for simulate chat method
        $aiService = Mockery::mock(AIService::class)->makePartial();

        $fakeAiResponse = json_encode([
            'category' => 'technical',
            'priority' => 'high'
        ]);

        $aiService->shouldReceive('chat')->once()->andReturn($fakeAiResponse);

        $schema = [
            'required' => ['category', 'priority'],
            'properties' => [
                'category' => ['type' => 'string'],
                'priority' => ['type' => 'string']
            ]
        ];

        $result = $aiService->structured("test prompt", $schema);

        $this->assertIsArray($result);
        $this->assertEquals('technical', $result['category']);
    }
}
