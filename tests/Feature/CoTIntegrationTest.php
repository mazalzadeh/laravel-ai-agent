<?php

namespace Tests\Feature;

use App\AI\CoT\CoTService;
use App\AI\CoT\DTOs\CoTResult;
use App\Services\AIClientInterface;
use Mockery;
use Tests\TestCase;

class CoTIntegrationTest extends TestCase
{
    /**
     * Clean up Mockery instances.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_cot_service_resolves_from_container_and_executes_successfully(): void
    {
        // Mocking the low-level client via the container
        $mockClient = Mockery::mock(AIClientInterface::class);
        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn([
                'content' => "<thought>\nCalculated step by step.\n</thought>\n<answer>\nCompleted\n</answer>",
            ]);

        $this->app->instance(AIClientInterface::class, $mockClient);

        $service = $this->app->make(CoTService::class);

        $result = $service->ask('Solve this task');

        $this->assertInstanceOf(CoTResult::class, $result);
        $this->assertEquals('Calculated step by step.', $result->getReasoning());
        $this->assertEquals('Completed', $result->getAnswer());
    }
}
