<?php

namespace Tests\Feature;

use App\Services\AIService;
use App\Services\DocumentAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use App\AI\Schema\SummarySchema;
use Tests\TestCase;

class DocumentAnalysisTest extends TestCase
{
    public function test_it_analyzes_document_successfully()
    {
        $mockedResponse = [
            "summary" => "This is a test summary",
            "keywords" => ["php", "laravel", "ai"],
            "sentiment" => "positive"
        ];

        $aiMock = Mockery::mock(AIService::class);
        $aiMock->shouldReceive('structured')->once()
            ->with(Mockery::type('string'), SummarySchema::schema())
            ->andReturn($mockedResponse);

        $service = new DocumentAnalysisService($aiMock);

        $result = $service->summarize("Some sample text to summarize");

        $this->assertEquals("This is a test summary", $result['summary']);
        $this->assertContains("laravel", $result['keywords']);
    }
}
