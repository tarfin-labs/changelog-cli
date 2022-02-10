<?php

namespace ChangelogCLI\Providers;

use ChangelogCLI\Changelog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Changelog::class, function ($app) {
            return new Changelog();
        });
    }
}
