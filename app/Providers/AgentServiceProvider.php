<?php

namespace App\Providers;

use App\Agent\Contracts\ConversationMemoryInterface;
use App\Agent\Services\RedisConversationMemory;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ConversationMemoryInterface::class,function($app){
            $ttl=(int)config('agent.memory.ttl', 3600);
            $windowSize=(int)config('agent.memory.window_size', 10);

            return new RedisConversationMemory(ttl:$ttl,windowSize:$windowSize);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
