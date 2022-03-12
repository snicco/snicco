<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Closure;
use LogicException;
use Snicco\Component\HttpRouting\Controller\RedirectController;
use Snicco\Component\HttpRouting\Controller\ViewController;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\Condition\IsAdminDashboardRequest;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function array_merge;
use function array_pop;
use function array_reverse;
use function gettype;
use function is_string;
use function trim;

/**
 * @interal
 *
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class Configurator implements WebRoutingConfigurator, AdminRoutingConfigurator
{
    /**
     * @var list<RouteGroup>
     */
    private array $group_stack = [];

    private AdminAreaPrefix $admin_dashboard_prefix;

    /**
     * @var array{
     *     namespace?:string,
     *     prefix?:UrlPath,
     *     name?:string,
     *     middleware?: string[]
     * } $extra_attributes
     */
    private array $delegate_attributes = [];

    private bool $locked = false;

    /**
     * @var array<string,AdminMenuItem>
     */
    private array $menu_items = [];

    private ?Route $current_parent_route = null;

    /**
     * @var array<string,Route>
     */
    private array $routes = [];

    public function __construct(AdminAreaPrefix $admin_dashboard_prefix)
    {
        $this->admin_dashboard_prefix = $admin_dashboard_prefix;
    }

    public function page(
        string $name,
        string $path,
        $action = Route::DELEGATE,
        ?array $menu_attributes = [],
        $parent = null
    ): Route {
        $this->validateThatDelegatedAttributesAreEmpty($name);

        $this->validateThatNoAdminPrefixesAreSet($path, $name);

        $route = $this->createRoute($name, $this->admin_dashboard_prefix->appendPath($path), ['GET'], $action);
        $route->condition(IsAdminDashboardRequest::class);

        $this->validateThatAdminRouteHasNoSegments($route);

        // It makes no sense to have a menu item without a dedicated action to handle it.
        if (Route::DELEGATE === $action && ! empty($menu_attributes)) {
            throw new BadRouteConfiguration(sprintf(
                'Route [%s] can not have an admin menu item without an action.',
                $name
            ));
        }

        // A menu item should explicitly not be added.
        if (null === $menu_attributes) {
            return $route;
        }

        if (Route::DELEGATE === $action) {
            return $route;
        }

        $this->addMenuItem($route, $menu_attributes, $parent);

        return $route;
    }

    public function subPages(Route $parent_route, Closure $routes): void
    {
        if (isset($this->current_parent_route)) {
            throw new BadRouteConfiguration(sprintf('Nested calls to [%s] are not possible.', __METHOD__));
        }

        $this->current_parent_route = $parent_route;

        $this->middleware($parent_route->getMiddleware())
            ->group($routes);

        $this->current_parent_route = null;
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion {@see https://github.com/vimeo/psalm/issues/7644}
     */
    public function group(Closure $create_routes, array $extra_attributes = []): void
    {
        $attributes = array_merge($this->delegate_attributes, $extra_attributes);
        $this->delegate_attributes = [];

        $this->updateGroupStack(new RouteGroup($attributes));

        $create_routes($this);

        $this->deleteCurrentGroup();
    }

    public function middleware($middleware): self
    {
        $this->delegate_attributes[RoutingConfigurator::MIDDLEWARE_KEY] = Arr::toArray($middleware);

        return $this;
    }

    public function name(string $name): self
    {
        $this->delegate_attributes[RoutingConfigurator::NAME_KEY] = $name;

        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->delegate_attributes[RoutingConfigurator::NAMESPACE_KEY] = $namespace;

        return $this;
    }

    /**
     * @psalm-suppress UnresolvableInclude
     * @psalm-suppress MixedAssignment
     *
     * @param mixed $file_or_closure
     */
    public function include($file_or_closure): void
    {
        $routes = $file_or_closure;
        if (! $routes instanceof Closure) {
            Assert::string($file_or_closure, '$file_or_closure has to be a string or a closure.');
            Assert::readable($file_or_closure, sprintf('The file %s is not readable.', $file_or_closure));
            Assert::isInstanceOf(
                $routes = require $file_or_closure,
                Closure::class,
                sprintf(
                    "Requiring the file [%s] has to return a closure.\nGot: [%s]",
                    $file_or_closure,
                    gettype($file_or_closure)
                )
            );
        }

        /** @var Closure(self):void $routes */
        $this->group($routes);
    }

    public function configuredRoutes(): Routes
    {
        return new RouteCollection($this->routes);
    }

    public function items(): array
    {
        return array_values($this->menu_items);
    }

    public function permanentRedirect(string $from_path, string $to_path, array $query = []): Route
    {
        return $this->redirect($from_path, $to_path, 301, $query);
    }

    public function redirect(string $from_path, string $to_path, int $status = 302, array $query = []): Route
    {
        $route = $this->any(
            $this->redirectRouteName($from_path, $to_path),
            $from_path,
            [RedirectController::class, 'to']
        );
        $route->defaults([
            'to' => $to_path,
            'status' => $status,
            'query' => $query,
        ]);

        return $route;
    }

    public function temporaryRedirect(string $from_path, string $to_path, array $query = [], int $status = 307): Route
    {
        return $this->redirect($from_path, $to_path, $status, $query);
    }

    public function redirectAway(string $from_path, string $location, int $status = 302): Route
    {
        $name = $this->redirectRouteName($from_path, $location);

        return $this->any($name, $from_path, [RedirectController::class, 'away'])->defaults([
            'location' => $location,
            'status' => $status,
        ]);
    }

    public function redirectToRoute(string $from_path, string $route, array $arguments = [], int $status = 302): Route
    {
        $name = $this->redirectRouteName($from_path, $route);

        return $this->any($name, $from_path, [RedirectController::class, 'toRoute'])->defaults([
            'route' => $route,
            'arguments' => $arguments,
            'status' => $status,
        ]);
    }

    public function view(string $path, string $view, array $data = [], int $status = 200, array $headers = []): Route
    {
        $name = 'view:' . Str::afterLast($view, '/');

        $route = $this->get($name, $path, ViewController::class);
        $route->defaults([
            'view' => $view,
            'data' => $data,
            'status' => $status,
            'headers' => $headers,
        ]);

        return $route;
    }

    public function get(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['GET'], $action);
    }

    public function post(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['POST'], $action);
    }

    public function put(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['PUT'], $action);
    }

    public function patch(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['PATCH'], $action);
    }

    public function delete(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['DELETE'], $action);
    }

    public function options(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, ['OPTIONS'], $action);
    }

    public function prefix(string $prefix): self
    {
        $this->delegate_attributes[RoutingConfigurator::PREFIX_KEY] = UrlPath::fromString($prefix);

        return $this;
    }

    public function fallback(
        $fallback_action,
        array $dont_match_request_including = ['favicon', 'robots', 'sitemap']
    ): Route {
        Assert::allString($dont_match_request_including, 'All fallback excludes have to be strings.');

        $dont_match_request_including[] = trim($this->admin_dashboard_prefix->asString(), '/');

        $regex = sprintf('(?!%s).+', implode('|', $dont_match_request_including));

        $route = $this->any(Route::FALLBACK_NAME, '/{path}', $fallback_action)
            ->requirements([
                'path' => $regex,
            ])
            ->condition(RouteCondition::NEGATE, IsAdminDashboardRequest::class);

        $this->locked = true;

        return $route;
    }

    public function any(string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, Route::ALL_METHODS, $action);
    }

    public function match(array $verbs, string $name, string $path, $action = Route::DELEGATE): Route
    {
        return $this->addWebRoute($name, $path, array_map('strtoupper', $verbs), $action);
    }

    private function validateThatDelegatedAttributesAreEmpty(string $route_name): void
    {
        if (! empty($this->delegate_attributes)) {
            throw BadRouteConfiguration::becauseDelegatedAttributesHaveNotBeenGrouped($route_name);
        }
    }

    private function validateThatNoAdminPrefixesAreSet(string $path, string $name): void
    {
        $prefix = $this->admin_dashboard_prefix->asString();
        if (UrlPath::fromString($path)->startsWith($prefix)) {
            throw BadRouteConfiguration::becauseAdminRouteWasAddedWithHardcodedPrefix($name, $prefix);
        }
    }

    private function validateThatAdminRouteHasNoSegments(Route $route): void
    {
        if ([] !== $route->getSegmentNames()) {
            throw BadRouteConfiguration::becauseAdminRouteHasSegments($route->getName());
        }
    }

    /**
     * @param Route|string|null $parent_route
     * @param array{
     *     menu_title?: string,
     *     page_title?: string,
     *     icon?: string,
     *     capability?: string,
     *     position?: int
     * } $attributes
     */
    private function addMenuItem(Route $route, array $attributes, $parent_route): void
    {
        $this->validateParentPageType($parent_route);

        $parent_slug = null;

        $parent_route ??= $this->current_parent_route ?? null;

        if (is_string($parent_route)) {
            $this->validateThatParentHasNoAdminPrefixSet($parent_route, $route->getName());
            $parent_route = $this->admin_dashboard_prefix->appendPath($parent_route);
            $parent_slug = $parent_route;
        }

        if ($parent_route instanceof Route) {
            $parent_name = $parent_route->getName();

            if (! isset($this->menu_items[$parent_name])) {
                throw new BadRouteConfiguration(
                    sprintf(
                        'Can not use route [%s] as a parent for [%s] because it has no menu item.',
                        $parent_name,
                        $route->getName()
                    )
                );
            }

            $parent_slug = $this->menu_items[$parent_name];
        }

        if ($parent_slug instanceof AdminMenuItem && $parent_slug->isChild()) {
            /** @var Route $parent_route */
            $parent_name = $parent_route->getName();

            throw new BadRouteConfiguration(
                sprintf(
                    'Can not use route [%s] as a parent for route [%s] because [%s] is already a child of parent slug [%s].',
                    $parent_name,
                    $route->getName(),
                    $parent_name,
                    (string) $parent_slug->parentSlug()
                )
            );
        }

        if ($parent_slug) {
            $this->validateSlugCompatibility($parent_slug, $route);
        }

        if ($parent_slug instanceof AdminMenuItem) {
            $parent_slug = $parent_slug->slug()
                ->asString();
        }

        $menu_item = AdminMenuItem::fromRoute($route, $attributes, $parent_slug);
        $this->menu_items[$route->getName()] = $menu_item;
    }

    /**
     * @param Route|string|null $parent
     */
    private function validateParentPageType($parent): void
    {
        if (null === $parent) {
            return;
        }

        if (! is_string($parent) && ! $parent instanceof Route) {
            throw new BadRouteConfiguration('$parent has to be a string or an instance of Route.');
        }

        if (isset($this->current_parent_route)) {
            throw new BadRouteConfiguration(
                sprintf(
                    'You can not pass route/parent_slug [%s] as the last argument during a call to subPages().',
                    ($parent instanceof Route ? $parent->getName() : $parent)
                )
            );
        }
    }

    private function validateThatParentHasNoAdminPrefixSet(string $parent, string $name): void
    {
        $prefix = $this->admin_dashboard_prefix->asString();
        if (UrlPath::fromString($parent)->startsWith($prefix)) {
            throw new BadRouteConfiguration(
                "You should not add the prefix [{$prefix}] to the parent slug of pages.\nAffected route [{$name}]."
            );
        }
    }

    /**
     * @param AdminMenuItem|string $parent_item
     *
     * @throws LogicException
     */
    private function validateSlugCompatibility($parent_item, Route $child_route): void
    {
        if ($parent_item instanceof AdminMenuItem) {
            $parent_slug = $parent_item->slug()
                ->asString();
            $compare_against = Str::beforeLast($parent_slug, '/');
        } else {
            $parent_slug = $parent_item;
            $compare_against = $parent_item;
        }

        $route_pattern = $child_route->getPattern();

        if (! UrlPath::fromString($route_pattern)->startsWith($compare_against)) {
            throw new BadRouteConfiguration(
                "Route pattern [{$route_pattern}] is incompatible with parent slug [{$parent_slug}].\nAffected route [{$child_route->getName()}]."
            );
        }
    }

    /**
     * @param array{0:class-string, 1:string}|class-string|string $action
     * @param string[]                                            $methods
     */
    private function addWebRoute(string $name, string $path, array $methods, $action): Route
    {
        $this->validateThatDelegatedAttributesAreEmpty($name);

        if ($this->locked) {
            throw BadRouteConfiguration::becauseFallbackRouteIsAlreadyRegistered($name);
        }

        if (UrlPath::fromString($path)->startsWith($this->admin_dashboard_prefix->asString())) {
            throw BadRouteConfiguration::becauseWebRouteHasAdminPrefix($name);
        }

        return $this->createRoute($name, $path, $methods, $action);
    }

    private function redirectRouteName(string $from, string $to): string
    {
        return sprintf('redirect_route:%s:%s', $from, $to);
    }

    /**
     * @param array{0: class-string, 1: string}|class-string|string $controller
     * @param string[]                                              $methods
     */
    private function createRoute(string $name, string $path, array $methods, $controller): Route
    {
        // Quick check to see if the developer swapped the arguments by accident.
        Assert::notStartsWith($name, '/');

        $path = $this->applyGroupPrefix(UrlPath::fromString($path));
        $name = $this->applyGroupName($name);
        $namespace = $this->applyGroupNamespace();

        $route = Route::create($path->asString(), $controller, $name, $methods, $namespace);

        $this->addGroupAttributes($route);

        $this->routes[$route->getName()] = $route;

        return $route;
    }

    private function applyGroupPrefix(UrlPath $path): UrlPath
    {
        $current = $this->currentGroup();
        if (! $current instanceof RouteGroup) {
            return $path;
        }

        return $path->prepend($current->prefix);
    }

    private function applyGroupName(string $route_name): string
    {
        $current = $this->currentGroup();
        if (! $current instanceof RouteGroup) {
            return $route_name;
        }

        $g = trim($current->name, '.');

        if ('' === $g) {
            return $route_name;
        }

        return sprintf('%s.%s', $g, $route_name);
    }

    private function applyGroupNamespace(): string
    {
        $current = $this->currentGroup();
        if (! $current instanceof RouteGroup) {
            return '';
        }

        return $current->namespace;
    }

    private function addGroupAttributes(Route $route): void
    {
        $current = $this->currentGroup();
        if (! $current instanceof RouteGroup) {
            return;
        }

        foreach ($current->middleware as $middleware) {
            $route->middleware($middleware);
        }
    }

    private function updateGroupStack(RouteGroup $group): void
    {
        $current = $this->currentGroup();
        if (null !== $current) {
            $group = $group->mergeWith($current);
        }

        $this->group_stack[] = $group;
    }

    private function currentGroup(): ?RouteGroup
    {
        if ([] === $this->group_stack) {
            return null;
        }

        return array_reverse($this->group_stack)[0];
    }

    private function deleteCurrentGroup(): void
    {
        array_pop($this->group_stack);
    }
}
