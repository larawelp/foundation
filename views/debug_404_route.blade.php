@php
    use LaraWelP\Foundation\Routing\WpRouteActionResolver;
    $show = !app()->environment('production') && isset(WpRouteActionResolver::$triedActions) && WpRouteActionResolver::$triedActions->totalActionsAttempted > 0;
    if(!$show) {
        return;
    }
@endphp

<div>
    LaraWelP tried the following actions to resolve the request, before returning 404:
</div>

@php
    $handler = function ($var, string $label = null) {
        $cloner = new \Symfony\Component\VarDumper\Cloner\VarCloner();
        $dumper = new \Symfony\Component\VarDumper\Dumper\HtmlDumper();
        $dumper->setTheme('light');
        $var = $cloner->cloneVar($var);

        if (null !== $label) {
            $var = $var->withContext(['label' => $label]);
        }

        $dumper->dump($var);
    };
    \Symfony\Component\VarDumper\VarDumper::setHandler($handler);
    \Symfony\Component\VarDumper\VarDumper::dump(WpRouteActionResolver::$triedActions)
@endphp

This page is shown because app()->environment('production') is false and you can figure out how to respond to the request.
<br/>

In the output above you get a very helpful class that lists any tried actions in order (wither a controller and method either a view.
