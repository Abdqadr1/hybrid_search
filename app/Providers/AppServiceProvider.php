<?php

namespace App\Providers;

use App\Contracts\EmbeddingGenerator;
use App\Services\OpenAIEmbeddingGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EmbeddingGenerator::class, OpenAIEmbeddingGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
