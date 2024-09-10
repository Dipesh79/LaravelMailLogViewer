<?php

namespace Dipesh79\LaravelMailLogViewer;

use Illuminate\Support\ServiceProvider;

class LaravelMailLogViewerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (method_exists($this, 'loadRoutesFrom')) {
            $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        }

        if (method_exists($this, 'loadViewsFrom')) {
            $this->loadViewsFrom(__DIR__ . '/views', 'emaillogviewer');
        }

        if (method_exists($this, 'publishes')) {
            $this->publishes([
                __DIR__ . '/config/laravel-mail-log-viewer.php' => config_path('laravel-mail-log-viewer.php'),
            ], 'config');

        }
    }
}
