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
    }
}