<?php

# global helpers
namespace {

    use Corcel\Model\Post;
    use Illuminate\Database\Eloquent\Builder;
    use Illuminate\View\Compilers\BladeCompiler;
    use Illuminate\View\Engines\CompilerEngine;
    use Illuminate\View\View;
    use LaraWelP\Foundation\Support\Wp\Query\QueryResults;

    if (!function_exists('public_url')) {
        /**
         * Generate an public_url path for the application.
         *
         * @param string $path
         *
         * @param bool $cacheBustingQuery
         *
         * @return string
         */
        function public_url($path = '', $cacheBustingQuery = true)
        {
            static $template_directory_uri;

            if (is_null($template_directory_uri)) {
                $template_directory_uri = get_template_directory_uri();
            }

            $public_url = $template_directory_uri . '/public' . ($path ? "/$path" : $path);

            if ($cacheBustingQuery) {
                // Get the full path to the asset.
                $realPath = public_path($path);

                if (!file_exists($realPath)) {
                    return $public_url;
                }

                // Get the last updated timestamp of the file.
                $timestamp = filemtime($realPath);

                // Add bust-query-string to url.
                $public_url .= '?' . $timestamp;
            }

            return $public_url;
        }
    }

    if (!function_exists('get_compiled_path')) {
        /**
         * Get the compiled path of a view.
         *
         * @param View $view
         *
         * @return string
         */
        function get_compiled_path(View $view)
        {
            /** @type CompilerEngine $engine */
            $engine = $view->getEngine();
            /** @type BladeCompiler $compiler */
            $compiler = $engine->getCompiler();
            $path = $view->getPath();
            if ($compiler->isExpired($path)) {
                $compiler->compile($path);
            }

            $compiled = $compiler->getCompiledPath($path);

            return $compiled;
        }
    }

    if (!function_exists('setup_the_post')) {
        function setup_the_post($the_post)
        {
            global $post;

            $post = $the_post;
            setup_postdata($the_post);
        }
    }

    Builder::macro('queriedModels', function () {
        global $wp_query;
        $posts = [];

        if ($wp_query) {
            $ids = array_map(function ($post) {
                return $post->ID;
            }, (array)$wp_query->posts);
            $models = Post::query()->whereIn('ID', $ids)->get();
            return array_map(function ($id) use ($models) {
                return $models->firstWhere('ID', $id);
            }, $ids);
        }

        return QueryResults::create($posts, $wp_query);
    });
}

# namespaced helpers
namespace LaraWelP {

    use LaraWelP\Foundation\Routing\WpRouteActionResolver;

    function view_data(): array {
        return WpRouteActionResolver::$viewData;
    }
}