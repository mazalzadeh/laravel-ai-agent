<?php

namespace Tests\Unit\Agent;

use App\Services\AIClientInterface;
use App\Services\FakeOpenAIClient;
use App\Agent\Services\AgentOrchestrator;
use Illuminate\Support\Facades\App;
use Tests\TestCase;


class AgentContainerBindingTest extends TestCase
{
    public function test_ai_client_interface_resolves_to_fake_client_by_default(): void
{
    config(['services.openai.mock' => true]);

    $client = App::make(AIClientInterface::class);

    $this->assertInstanceOf(FakeOpenAIClient::class, $client);
    $this->assertInstanceOf(AIClientInterface::class, $client);
    }

    /*public function test_ai_client_interface_resolves_to_real_client_when_mock_is_disabled(): void
    {
        config(['services.openai.mock' => false]);

        $client = App::make(AIClientInterface::class);

        $this->assertInstanceOf(AIClientInterface::class, $client);
    }*/

    public function test_agent_orchestrator_resolves_from_container(): void
    {
        $orchestrator = App::make(AgentOrchestrator::class);

        $this->assertInstanceOf(AgentOrchestrator::class, $orchestrator);
    }


    public function test_agent_orchestrator_is_not_registered_as_singleton(): void
{
    $first = App::make(AgentOrchestrator::class);
    $second = App::make(AgentOrchestrator::class);

    $this->assertNotSame($first, $second);
}
}
