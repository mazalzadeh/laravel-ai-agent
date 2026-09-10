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

        $this->app->bind(AIClientInterface::class, function () {
            $isMock = config('services.openai.mock', true);

            return $isMock
                ? new FakeOpenAIClient()
                : new OpenAIClient();
        });
        /*$this->app->bind(AIClientInterface::class, function(){
            //if OPENAI_MOCK=true use Fake
            if(env('OPENAI_MOCK', true)){
                Log::info('Binding FakeOpenAIClient');
                return new FakeOpenAIClient();
            }

            Log::info('Binding OpenAIClient');
            return new OpenAIClient();
        });*/
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
