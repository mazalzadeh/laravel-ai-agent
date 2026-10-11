<?php

namespace App\Providers;

use App\Services\AIClientInterface;
use App\Services\FakeOpenAIClient;
use App\Services\OpenAIClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use App\AI\Context\ContextFormatter;
use App\AI\Context\PlainTextContextFormatter;
use App\AI\Context\ContextRetriever;
use App\AI\Context\VectorContextRetriever;
use App\AI\CoT\CoTOutputParser;
use App\AI\CoT\CoTService;
use App\AI\CoT\Prompts\CoTPromptTemplate;
use App\Agent\Contracts\ConversationMemoryInterface;
use App\Agent\Contracts\ResponseCacheInterface;
use App\Agent\Services\RedisConversationMemory;
use App\Agent\Services\RedisResponseCache;


class AppServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        ContextFormatter::class => PlainTextContextFormatter::class,
        ContextRetriever::class => VectorContextRetriever::class,
    ];


    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CoTService::class, function ($app) {
            return new CoTService(
                $app->make(AIClientInterface::class),
                $app->make(CoTPromptTemplate::class),
                $app->make(CoTOutputParser::class),
            );
        });

        $this->app->singleton(ConversationMemoryInterface::class, function ($app) {
            return new RedisConversationMemory(ttl: 3600, windowSize: 10);
        });

        $this->app->singleton(ResponseCacheInterface::class, function ($app) {
            return new RedisResponseCache(ttl: 86400);
        });

        $this->app->bind(AIClientInterface::class, function () {
            $isMock = config('services.openai.mock', true);

            return $isMock
                ? new FakeOpenAIClient()
                : new OpenAIClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
