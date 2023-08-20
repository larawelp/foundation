<?php

namespace LaraWelP\Foundation\Routing;

#[\AllowDynamicProperties]
class LookupHierarchy
{
    public int $totalActionsAttempted = 0;

    public string $hint = 'Create any view listed as action_* or any controller and action listed in this class.';

    public string $viewsPath = '';

    public string $controllersPath = '';

    public function __construct()
    {
        $this->viewsPath = app()->basePath('resources/views');

        $this->controllersPath = app()->basePath('app/Http/Controllers');
    }
}
