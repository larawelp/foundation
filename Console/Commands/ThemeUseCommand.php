<?php

namespace LaraWelP\Foundation\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;

class ThemeUseCommand extends Command
{
    protected $signature = 'theme:use {--ask}';

    protected $description = 'Sets the active WordPress theme to the LaraWelP theme.';

    public function handle()
    {
        if($this->option('ask')) {
            $confirm  = confirm(
                label: 'Do you want to set the LaraWelP theme as active in WordPress?',
                default: true
            );

            if(!$confirm) {
                return;
            }
        }

        $this->warn('Setting LaraWelP theme as active in WordPress...');

        $this->setActiveTheme();
    }

    private function setActiveTheme()
    {
        $dirname = wp_basename(app()->basePath());

        $current_theme = get_option('template');

        $this->info('Current theme: ' . $current_theme);

        if($current_theme === $dirname) {
            $this->warn('The LaraWelP theme is already active in WordPress.');
            return;
        }

        switch_theme($dirname);

        $this->info('The LaraWelP theme is now active in WordPress.');
    }
}