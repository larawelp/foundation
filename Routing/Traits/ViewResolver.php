<?php

namespace LaraWelP\Foundation\Routing\Traits;

use Illuminate\Support\Arr;
use LaraWelP\Foundation\Routing\WpRouteActionResolver;

trait ViewResolver
{
    /**
     * @param string $defaultView
     * @param array $data
     * @param bool $injectDefaultData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    protected function resolveView(string $defaultView, array $data = [], bool $injectDefaultData = true)
    {
        $wpRouteActionResolver = new WpRouteActionResolver();
        $wpRouteActionResolver->resolveController = false;
        $wpRouteActionResolver->injectDefaultData = $injectDefaultData;
        $action = $wpRouteActionResolver->resolve();

        if ($action === null) {
            return $this->makeView($defaultView, $data);
        }

        $viewData = $action[2];
        return $this->makeView(Arr::pull($viewData, 'view'), $viewData, $data);
    }

    /**
     * @param string $view
     * @param array $data
     * @param array $mergeData
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    private function makeView(string $view, array $data = [], array $mergeData = [])
    {
        if (method_exists($this, 'view')) {
            return $this->view($view, $data, $mergeData);
        }

        return view($view, $data, $mergeData);
    }
}
