<?php

namespace Eelcol\LaravelMeilisearch;

use Eelcol\LaravelMeilisearch\Commands\CreateIndex;
use Eelcol\LaravelMeilisearch\Commands\SetIndexSettings;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchConnector;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Illuminate\Support\ServiceProvider;

class LaravelMeilisearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/meilisearch.php' => config_path('meilisearch.php'),
        ], 'laravel-meilisearch');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/meilisearch.php', 'meilisearch');

        $this->app->singleton('meilisearch', function ($app) {
            return new MeilisearchConnector(config('meilisearch'));
        });

        $this->app->bind('meilisearch-query', function ($app) {
            return new MeilisearchQuery();
        });

        $this->commands([
            SetIndexSettings::class,
            CreateIndex::class,
        ]);
    }
}
