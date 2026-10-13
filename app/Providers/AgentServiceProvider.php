<?php

namespace App\Providers;

use App\Agent\Contracts\ConversationMemoryInterface;
use App\Agent\Services\RedisConversationMemory;
use App\Agent\Services\ToolExecutionHandler;
use App\Agent\Tools\CalculatorTool;
use App\Agent\Services\ToolRegistry;
use App\Services\AIClientInterface;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry();
            $registry->register(new CalculatorTool());
            return $registry;
        });

        $this->app->singleton(ToolExecutionHandler::class, function ($app) {
            return new ToolExecutionHandler(
                $app->make(AIClientInterface::class),
                $app->make(ToolRegistry::class)
            );
        });

        $this->app->bind(ConversationMemoryInterface::class, function ($app) {
            $ttl = (int)config('agent.memory.ttl', 3600);
            $windowSize = (int)config('agent.memory.window_size', 10);

            return new RedisConversationMemory(ttl: $ttl, windowSize: $windowSize);
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
