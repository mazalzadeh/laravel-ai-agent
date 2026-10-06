<?php

namespace Tests\Feature;

use App\Agent\Contracts\ConversationMemoryInterface;
use App\Agent\Services\RedisConversationMemory;
use Tests\TestCase;

class AgentContainerTest extends TestCase
{
    public function test_it_resolves_conversation_memory_contract(): void
    {
        $memory = app(ConversationMemoryInterface::class);

        $this->assertInstanceOf(ConversationMemoryInterface::class, $memory);
        $this->assertInstanceOf(RedisConversationMemory::class, $memory);
    }
}
