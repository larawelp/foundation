<?php

namespace LaraWelP\Foundation\Providers;

class FoundationServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->loadViewsFrom(__DIR__ . '/../views', 'larawelp');

        $this->publishes(
            [
                __DIR__ . '/../views' => resource_path('views/vendor/larawelp'),
            ],
            'larawelp-views'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../config/larawelp.php',
            'larawelp'
        );

        $this->publishes([
            __DIR__ . '/../config/larawelp.php' => config_path('larawelp.php'),
        ], 'larawelp-config');
    }
}