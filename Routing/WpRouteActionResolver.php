<?php

namespace LaraWelP\Foundation\Routing;

use Corcel\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use LaraWelP\Foundation\Support\Wp\Model\Post;
use LaraWelP\Foundation\Support\Wp\Model\Term;
use LaraWelP\Foundation\Support\Wp\Model\User;

class WpRouteActionResolver
{
    public static $viewDirectory = 'wp';
    public static $modelNamespace = '\\App\\Models\\Wp\\';
    public static $controllerNamespace = '\\App\\Http\\Controllers\\Wp\\';

    public static $triedActions;

    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * @var mixed
     */
    protected $queriedObject;

    /**
     * @var bool
     */
    public $resolveController = true;

    /**
     * @var bool
     */
    public $injectDefaultData = true;

    public static array $viewData = [];

    /**
     * WpRouteActionResolver constructor.
     */
    public function __construct()
    {
        $this->view = app('view');
        $this->queriedObject = get_queried_object();
    }

    /**
     * Return the action for the discovered action
     */
    public function resolve(): ?array
    {
        $queriedObject = $this->queriedObject;

        $returnedAction = null;

        if ((is_home() || is_front_page()) && ($action = $this->getHomeAction())) {
            $returnedAction = $action;
        }

        if (is_page() && $queriedObject instanceof \WP_Post && ($action = $this->getPageAction())) {
            $returnedAction = $action;
        }

        if (is_singular() && $queriedObject instanceof \WP_Post && ($action = $this->getSingularAction())) {
            $returnedAction = $action;
        }

        if (
            is_post_type_archive() &&
            $queriedObject instanceof \WP_Post_Type &&
            ($action = $this->getPostTypeArchiveAction())
        ) {
            $returnedAction = $action;
        }

        if (
            (is_category() || is_tag() || is_tax()) &&
            $queriedObject instanceof \WP_Term &&
            ($action = $this->getTermAction())
        ) {
            $returnedAction = $action;
        }

        if (is_author() && $queriedObject instanceof \WP_User && ($action = $this->getAuthorAction())) {
            $returnedAction = $action;
        }

        if (is_search() && ($action = $this->getSearchAction())) {
            $returnedAction = $action;
        }

        if (is_archive() && ($action = $this->getArchiveAction())) {
            $returnedAction = $action;
        }

        if (is_404() && ($action = $this->getNotFoundAction())) {
            $returnedAction = $action;
        }

        if (is_array($returnedAction) && count($returnedAction) === 2) {
            [$action, $data] = $returnedAction;
            if (!class_exists($action)) {
                return null;
            }
            $methods = get_class_methods($action);
            if (!in_array($data, $methods)) {
                return null;
            }
        }

        return $returnedAction;
    }

    protected function getHomeAction(): ?array
    {
        $viewData = $this->injectDefaultData
            ? (is_front_page() && !is_home()
                ? $this->getDataForPostPage()
                : $this->getDataForArchivePage())
            : [];

        if ($action = $this->getGenericPageAction('home', $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getPageAction(): ?array
    {
        $post = new Post($this->queriedObject);
        $pageTemplate = $post->page_template;

        if ($pageTemplate && $pageTemplate !== 'default') {
            $hierarchy = ['template', Str::slug($pageTemplate)];
        } else {
            $hierarchy = $post
                ->ancestors()
                ->map(function (Post $post) {
                    return $post->wpPost()->post_name;
                })
                ->concat([$post->wpPost()->post_name])
                ->map(function ($slug) {
                    return urldecode($slug);
                })
                ->prepend('page');
        }

        $viewData = $this->injectDefaultData
            ? [
                'post' => $post,
            ]
            : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getSingularAction(): ?array
    {
        $modelClass = $this->getModelClassForPostType() ?? Post::class;
        $post = new $modelClass($this->queriedObject);

        if ($pageTemplate = $post->page_template) {
            $hierarchy = ['template', Str::slug($pageTemplate)];
        } else {
            $hierarchy = ['post', $this->getPostType()];
        }

        $viewData = $this->injectDefaultData
            ? [
                'post' => $post,
                'model' => (new \Corcel\Model\Post())->newFromBuilder((array)$post->wpPost()),
            ]
            : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getPostTypeArchiveAction(): ?array
    {
        $hierarchy = ['post-archive', $this->getPostType()];
        $viewData = $this->injectDefaultData ? $this->getDataForArchivePage() : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getTermAction(): ?array
    {
        $termModelClass = $this->getModelClassForTerm() ?? Term::class;
        $currentTerm = new $termModelClass($this->queriedObject);
        $currentTaxonomy = urldecode($currentTerm->taxonomy());

        $termHierarchy = $currentTerm
            ->ancestors()
            ->push($currentTerm)
            ->map(function (Term $term) {
                return urldecode($term->slug());
            })
            ->prepend($currentTaxonomy)
            ->prepend('taxonomy');

        $viewData = $this->injectDefaultData
            ? [
                'term' => $currentTerm,
            ] + $this->getDataForArchivePage()
            : [];

        if ($action = $this->getActionByHierarchy($termHierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getAuthorAction(): ?array
    {
        $userModelClass = $this->getModelClassForUser() ?? User::class;
        $author = new $userModelClass($this->queriedObject);
        $hierarchy = ['author', $author->nickname()];
        $viewData = $this->injectDefaultData
            ? [
                'author' => $author,
            ] + $this->getDataForArchivePage()
            : [];

        if ($action = $this->getActionByHierarchy($hierarchy, $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getSearchAction(): ?array
    {
        $viewData = $this->injectDefaultData
            ? [
                'keyword' => get_search_query(),
            ] + $this->getDataForArchivePage()
            : [];

        if ($action = $this->getActionByHierarchy(['search'], $viewData, 'archive')) {
            return $action;
        }

        return null;
    }

    protected function getArchiveAction(): ?array
    {
        $viewData = $this->injectDefaultData ? $this->getDataForArchivePage() : [];

        if ($action = $this->getGenericPageAction('archive', $viewData)) {
            return $action;
        }

        return null;
    }

    protected function getNotFoundAction(): ?array
    {
        if ($action = $this->getGenericPageAction('not-found')) {
            return $action;
        }

        return null;
    }

    protected function getGenericPageAction(string $type, $viewData = []): ?array
    {
        if ($action = $this->getActionByHierarchy([$type], $viewData)) {
            return $action;
        }

        return null;
    }

    /**
     * @param Collection|array $hierarchy
     * @param array|\Closure $viewData
     * @param string ...$fallbackGenericTypes
     * @return array|null
     */
    protected function getActionByHierarchy($hierarchy, $viewData = [], ...$fallbackGenericTypes): ?array
    {
        $hierarchy = is_array($hierarchy) ? new Collection($hierarchy) : $hierarchy;

        static::$viewData = $viewData instanceof \Closure ? $viewData() : $viewData;

        if ($this->resolveController && ($action = $this->getControllerActionByHierarchy($hierarchy))) {
            if($action = $this->checkAction($action)) {
                return $action;
            }
        }

        if ($action = $this->getViewActionByHierarchy($hierarchy, $viewData)) {
            if($action = $this->checkAction($action)) {
                return $action;
            }
        }

        foreach ($fallbackGenericTypes as $fallbackGenericType) {
            if ($action = $this->getGenericPageAction($fallbackGenericType, $viewData)) {
                return $action;
            }
        }

        return null;
    }

    protected function checkAction($action) {
        if (is_array($action) && count($action) === 2) {
            [$_action, $data] = $action;
            if (!class_exists($_action)) {
                return null;
            }
            $methods = get_class_methods($_action);
            if (!in_array($data, $methods)) {
                return null;
            }
        }
        return $action;
    }

    /**
     * @param Collection|array $hierarchy
     * @return array|null
     */
    protected function getControllerActionByHierarchy($hierarchy): ?array
    {
        $hierarchy = clone $hierarchy;

        while ($hierarchy->count() > 0) {
            $controller = $this->fullyQualifiedController(
                $hierarchy
                    ->map(function ($slug) {
                        return ucfirst(Str::camel($slug));
                    })
                    ->implode('\\')
            );


            $this->markTriedAction('Controller: ' . $controller . ' method index');
            if ($this->controllerExists($controller)) {
                $methods = get_class_methods($controller);
                return $this->getAction($controller);
            }

            $hierarchy->pop();
        }

        return null;
    }

    /**
     * @param Collection|array $hierarchy
     * @param array|\Closure $viewData
     * @return array|null
     */
    protected function getViewActionByHierarchy($hierarchy, $viewData = []): ?array
    {
        $hierarchy = clone $hierarchy;

        while ($hierarchy->count() > 0) {
            $view = $this->getViewPath($hierarchy->implode('.'));
            $this->markTriedAction('View: ' . app()->basePath('resources/views/' . $view));
            if ($this->viewExists($view)) {
                $data = $viewData instanceof \Closure ? $viewData() : $viewData;

                return $this->getViewAction($view, $data);
            }

            $hierarchy->pop();
        }

        return null;
    }

    protected function markTriedAction(mixed $action): void
    {
        if (static::$triedActions === null) {
            static::$triedActions = new LookupHierarchy();
        }
        static::$triedActions->totalActionsAttempted++;
        $name = 'tried_action_' . static::$triedActions->totalActionsAttempted;
        static::$triedActions->$name = $action;
    }

    protected function fullyQualifiedController(string $controllerBaseName): string
    {
        return static::$controllerNamespace . $controllerBaseName;
    }

    public function controllerExists(string $controllerClass): bool
    {
        return class_exists($controllerClass);
    }

    protected function getViewPath(string $view): string
    {
        return static::$viewDirectory . ".$view";
    }

    protected function viewExists(string $view): bool
    {
        return $this->view->exists($view);
    }

    protected function getViewAction(string $view, $data = []): array
    {
        return [ViewController::class, 'index', ['view' => $view] + $data];
    }

    protected function getModelClassForPostType(string $postType = null): ?string
    {
        $postType = $postType ?? $this->getPostType();

        $classBaseName = ucfirst(Str::camel($postType));
        $modelClass = $this->fullyQualifiedModel("Post\\$classBaseName");

        return class_exists($modelClass) ? $modelClass : null;
    }

    protected function getModelClassForTerm(string $taxonomy = null): ?string
    {
        $taxonomy = $taxonomy ?? $this->queriedObject->taxonomy;
        $classBaseName = ucfirst(Str::camel($taxonomy));
        $modelClass = $this->fullyQualifiedModel("Taxonomy\\$classBaseName");

        return class_exists($modelClass) ? $modelClass : null;
    }

    protected function getModelClassForUser(): ?string
    {
        $modelClass = $this->fullyQualifiedModel('User');

        return class_exists($modelClass) ? $modelClass : null;
    }

    protected function getPostType(): string
    {
        if ($this->queriedObject instanceof \WP_Post_Type) {
            return $this->queriedObject->name;
        }

        return get_post_type() ?: 'post';
    }

    protected function fullyQualifiedModel(string $modelBaseName): string
    {
        return static::$modelNamespace . $modelBaseName;
    }

    protected function getDataForArchivePage(): array
    {
        $modelClass = $this->getModelClassForPostType() ?? Post::class;

        return [
            'posts' => $modelClass::queriedPosts(),
            'models' => Model::queriedModels(),
        ];
    }

    protected function getDataForPostPage(): array
    {
        $modelClass = $this->getModelClassForPostType() ?? Post::class;
        $post = new $modelClass($this->queriedObject);

        return [
            'post' => $post,
            'model' => (new \Corcel\Model\Post())->newFromBuilder((array)$post->wpPost()),
        ];
    }

    protected function getAction(string $controller): array
    {
        return [$controller, 'index'];
    }
}
