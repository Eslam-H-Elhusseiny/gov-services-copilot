<?php

namespace App\Providers;

use App\Application\Copilot\Contracts\CaseRepository;
use App\Infrastructure\Copilot\EloquentCaseRepository;
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
