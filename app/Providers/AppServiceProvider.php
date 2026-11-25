<?php

namespace App\Providers;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Application\Copilot\Llm\LlmProviderManager;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Infrastructure\Copilot\EloquentCaseRepository;
use App\Infrastructure\Copilot\Embedding\GeminiEmbeddingProvider;
use App\Infrastructure\Copilot\Llm\GeminiProvider;
use App\Infrastructure\Copilot\Llm\GroqProvider;
use App\Infrastructure\Copilot\Vector\QdrantVectorIndex;
use App\Models\ServiceCase;
use App\Models\WorkflowRun;
use App\Policies\CopilotPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CaseRepository::class, EloquentCaseRepository::class);

        $this->app->singleton(GroqProvider::class, fn ($app) => new GroqProvider($app['config']['ai.providers.groq']));
        $this->app->singleton(GeminiProvider::class, fn ($app) => new GeminiProvider($app['config']['ai.providers.gemini']));

        $this->app->bind(LlmProviderManager::class, function ($app) {
            $providers = [
                $app->make(GroqProvider::class),
                $app->make(GeminiProvider::class),
            ];

            return new LlmProviderManager(
                $providers,
                $app['config']['ai.default'],
                $app['config']['ai.fallback'],
            );
        });

        $this->app->singleton(EmbeddingProvider::class, fn ($app) => new GeminiEmbeddingProvider($app['config']['embeddings']));

        $this->app->singleton(VectorIndex::class, fn ($app) => new QdrantVectorIndex($app['config']['vector']));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ServiceCase::class, CopilotPolicy::class);
        Gate::policy(WorkflowRun::class, CopilotPolicy::class);
    }
}
