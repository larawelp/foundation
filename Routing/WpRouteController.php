<?php

namespace LaraWelP\Foundation\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaraWelP\Foundation\Events\WhenFolioRegisters;
use LaraWelP\Foundation\Support\Providers\RouteServiceProvider;
use Laravel\Folio\FolioManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WpRouteController extends Controller
{
    public function dispatch(Request $request)
    {
        /** @var WpRouter $wpRouter */
        $wpRouter = app('wpRouter');

        $wpMiddleware = RouteServiceProvider::getWpMiddleware();
        if (empty($wpMiddleware)) {
            require $this->wpRoutes();
        } else {
            $wpRouter->middleware($wpMiddleware)->group($this->wpRoutes());
        }

        $resp404 = null;

        try {
            $response = $wpRouter->dispatch($request);
            if ($response->getStatusCode() !== 404) {
                return $response;
            } else {
                $resp404 = $response;
            }
        } catch (NotFoundHttpException $e) {
//            dd($e);

        }

        $folioEnabled = config('larawelp.enable_folio_integration', true);
        if (!class_exists(FolioManager::class) || !$folioEnabled) {
            if (isset(\LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions)) {
                \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->totalActionsAttempted++;
                $name = 'tried_action_' . \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->totalActionsAttempted;
                \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->{$name} = 'Tried to use Laravel Folio but it is not installed or enabled in config.';
            }
            $response = $this->get404Response($request);
            return $response;
        }

        return $this->handleLaravelFolio($request, $resp404);
    }

    protected function wpRoutes(): string
    {
        return base_path('routes/wp.php');
    }

    /**
     * @param Request $request
     * @param \Symfony\Component\HttpFoundation\Response|null $resp404
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|void
     */
    private function handleLaravelFolio(Request $request, ?\Symfony\Component\HttpFoundation\Response $resp404)
    {
        app('events')->dispatch(WhenFolioRegisters::EVENT_NAME);
        $manager = app(FolioManager::class);
        $folioHandler = (fn() => $this->handler())->call($manager);
        try {
            $response = $folioHandler($request);

            if ($response instanceof \Illuminate\Http\Response) {
                return $response;
            }
        } catch (NotFoundHttpException $e) {
            if (isset(\LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions)) {
                \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->totalActionsAttempted++;
                $name = 'tried_action_' . \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->totalActionsAttempted;
                \LaraWelP\Foundation\Routing\WpRouteActionResolver::$triedActions->{$name} = 'Tried to use Laravel Folio but no route matched. Doc: https://github.com/laravel/folio';
            }
            $response = $this->get404Response($request);
            return $response;
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    private function get404Response(Request $request)
    {
        $route = app('wpRouter')->getRouter()->getRoutes()->getByAction('App\Http\HandleNotFound@handle404');
        $route->bind($request);
        $response = $route->run();
        $response->setStatusCode(404);
        return $response;
    }
}
