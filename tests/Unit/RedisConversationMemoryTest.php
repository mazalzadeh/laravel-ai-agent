<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RedisConversationMemory;
use Illuminate\Support\Facades\Redis;

class RedisConversationMemoryTest extends TestCase
{
    protected string $sessionId = 'test_session_123';
    protected RedisConversationMemory $memory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->memory = new RedisConversationMemory(ttl: 3600);
        $this->memory->clear($this->sessionId);
    }


    protected function tearDown(): void
    {
        $this->memory->clear($this->sessionId);
        parent::tearDown();
    }


    public function test_can_add_and_retrieve_messages(): void
    {
        $this->memory->addMessage($this->sessionId, 'user', 'Hello AI');
        $this->memory->addMessage($this->sessionId, 'assistant', 'Hello, how can I help?');

        $messages = $this->memory->getMessage($this->sessionId);

        $this->assertCount(2, $messages);
        $this->assertEquals([
            ['role' => 'user', 'content' => 'Hello AI'],
            ['role' => 'assistant', 'content' => 'Hello, how can I help?'],
        ], $messages);
    }


    public function test_can_clear_session_memory(): void
    {
        $this->memory->addMessage($this->sessionId, 'user', 'Ping');
        $this->assertTrue($this->memory->clear($this->sessionId));
        $this->assertEmpty($this->memory->getMessage($this->sessionId));
    }
}
