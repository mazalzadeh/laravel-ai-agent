<?php

namespace App\Providers;

use App\Services\AIClientInterface;
use App\Services\FakeAIClient;
use App\Services\FakeOpenAIClient;
use App\Services\OpenAIClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
            Log::info('OPENAI_MOCK env: ' . var_export(env('OPENAI_MOCK'), true));
            Log::info('config(app.openai_mock): ' . var_export(config('app.openai_mock'), true));


        $this->app->bind(AIClientInterface::class, function(){
            //if OPENAI_MOCK=true use Fake
            if(env('OPENAI_MOCK', true)){
                Log::info('Binding FakeOpenAIClient');
                return new FakeOpenAIClient();
            }

            Log::info('Binding OpenAIClient');
            return new OpenAIClient();
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
