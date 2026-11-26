<?php

namespace App\Providers;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Application\Copilot\Document\Contracts\DocumentRepository;
use App\Application\Copilot\Document\IngestDocument;
use App\Application\Copilot\Llm\LlmProviderManager;
use App\Domain\Copilot\Document\DocumentParser;
use App\Domain\Copilot\Document\TextChunker;
use App\Domain\Copilot\Embedding\EmbeddingProvider;
use App\Domain\Copilot\Vector\VectorIndex;
use App\Infrastructure\Copilot\Document\DocxDocumentParser;
use App\Infrastructure\Copilot\Document\EloquentDocumentRepository;
use App\Infrastructure\Copilot\Document\PdfDocumentParser;
use App\Infrastructure\Copilot\Document\RoutingDocumentParser;
use App\Infrastructure\Copilot\EloquentCaseRepository;
use App\Infrastructure\Copilot\Embedding\GeminiEmbeddingProvider;
use App\Infrastructure\Copilot\Llm\GeminiProvider;
use App\Infrastructure\Copilot\Llm\GroqProvider;
use App\Infrastructure\Copilot\Vector\QdrantVectorIndex;
use App\Models\Document;
use App\Models\ServiceCase;
use App\Models\WorkflowRun;
use App\Policies\CopilotPolicy;
use App\Policies\DocumentPolicy;
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

        $this->app->singleton(DocumentRepository::class, EloquentDocumentRepository::class);

        $this->app->bind(DocumentParser::class, function ($app) {
            return new RoutingDocumentParser(
                $app->make(PdfDocumentParser::class),
                $app->make(DocxDocumentParser::class),
            );
        });

        $this->app->bind(IngestDocument::class, function ($app) {
            return new IngestDocument(
                $app->make(DocumentRepository::class),
                $app->make(DocumentParser::class),
                $app->make(TextChunker::class),
                $app->make(EmbeddingProvider::class),
                $app->make(VectorIndex::class),
                (int) $app['config']['documents.chunk_size'],
                (int) $app['config']['documents.chunk_overlap'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ServiceCase::class, CopilotPolicy::class);
        Gate::policy(WorkflowRun::class, CopilotPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
