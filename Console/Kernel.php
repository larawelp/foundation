<?php

namespace LaraWelP\Foundation\Console;

use Illuminate\Console\Application as Artisan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use LaraWelP\Foundation\Console\Commands\ThemeUseCommand;
use LaraWelP\Foundation\Providers\FoundationServiceProvider;

class Kernel extends ConsoleKernel
{
    /**
     * @var bool Flag to determine if load wordpress plugins.
     */
    protected $loadWordpressPlugins = false;

    /**
     * Create a new console kernel instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        // Used in `functions.php` so that we know we can stop from launching
        // the whole theme when in `Console-Mode`, because our goal is to use
        // some of the functions of WordPress.
        if (!defined('ARTISAN_BINARY')) {
            define('ARTISAN_BINARY', 'artisan');
        }

        // Load WordPress so we can use those functions WordPress supplies for us.
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', false);
        }

        // Prevent from loading plugins (it could be error-prone if load plugins).
        if ($this->loadWordpressPlugins === false) {
            define('WP_PLUGIN_DIR', '/NULL');
        }

        $wp_load = realpath($app->basePath() . '/../../../wp-load.php');

        if (file_exists($wp_load)) {
            require_once $wp_load;
        }

        parent::__construct($app, $events);

        app('events')
        ->listen(
            'bootstrapping: Illuminate\Foundation\Bootstrap\RegisterProviders',
            function (Application $app) {
                $app->register(FoundationServiceProvider::class);
            });

        Artisan::starting(function ($artisan) {
            \Event::dispatch('register.folio');
            $artisan->resolve(ThemeUseCommand::class);
        });
    }
}
