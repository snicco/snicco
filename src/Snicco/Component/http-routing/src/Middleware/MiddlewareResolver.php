<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use LogicException;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Controller\ControllerAction;
use Snicco\Component\HttpRouting\Exception\InvalidMiddleware;
use Snicco\Component\HttpRouting\Exception\MiddlewareRecursion;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\IsInterfaceString;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_merge;
use function array_reverse;
use function array_search;
use function array_unique;
use function array_values;
use function explode;

use const SORT_REGULAR;

final class MiddlewareResolver
{

    public const MIDDLEWARE_DELIMITER = ':';
    public const ARGUMENT_SEPARATOR = ',';

    /**
     * @var array<string, list<MiddlewareBlueprint>>
     */
    private array $middleware_groups = [
        RoutingConfigurator::FRONTEND_MIDDLEWARE => [],
        RoutingConfigurator::ADMIN_MIDDLEWARE => [],
        RoutingConfigurator::API_MIDDLEWARE => [],
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [],
    ];

    /**
     * @var array<string, class-string<MiddlewareInterface>>
     */
    private array $middleware_aliases = [];

    /**
     * @var list<class-string<MiddlewareInterface>>
     */
    private array $middleware_by_increasing_priority = [];

    /**
     * @var array<'admin'|'frontend'|'api'|'global',bool>
     */
    private array $always_run_if_no_route_matches = [
        RoutingConfigurator::GLOBAL_MIDDLEWARE => false,
        RoutingConfigurator::FRONTEND_MIDDLEWARE => false,
        RoutingConfigurator::ADMIN_MIDDLEWARE => false,
        RoutingConfigurator::API_MIDDLEWARE => false,
    ];

    /**
     * @var array<string,true>
     */
    private $unfinished_group_trace = [];

    private bool $is_cached = false;

    /**
     * @var array<string, array< array{class: class-string<MiddlewareInterface>, args: array<scalar>}>>
     */
    private array $route_map = [];

    /**
     * @var array<'admin'|'frontend'|'api'|'global', array<array{class: class-string<MiddlewareInterface>, args: array<scalar>}>> $request_type_map
     */
    private array $request_map = [];

    /**
     * @param array<
     *     RoutingConfigurator::FRONTEND_MIDDLEWARE |
     *     RoutingConfigurator::ADMIN_MIDDLEWARE |
     *     RoutingConfigurator::API_MIDDLEWARE |
     *     RoutingConfigurator::GLOBAL_MIDDLEWARE
     * > $always_run_if_no_route_matches
     * @param array<string,class-string<MiddlewareInterface>> $middleware_aliases
     * @param array<string,array<string|class-string<MiddlewareInterface>>> $middleware_groups
     * @param list<class-string<MiddlewareInterface>> $middleware_priority
     */
    public function __construct(
        array $always_run_if_no_route_matches = [],
        array $middleware_aliases = [],
        array $middleware_groups = [],
        array $middleware_priority = []
    ) {
        $this->addAlwaysRun($always_run_if_no_route_matches);
        $this->addAliases($middleware_aliases);
        $this->addMiddlewareGroups($middleware_groups);
        $this->addMiddlewarePriority($middleware_priority);
    }

    /**
     * @param array<string, array< array{class: class-string<MiddlewareInterface>, args: array<scalar>}>> $route_map
     *
     * @param array<'admin'|'frontend'|'api'|'global', array< array{class: class-string<MiddlewareInterface>, args: array<scalar>}>> $request_type_map
     */
    public static function fromCache(array $route_map, array $request_type_map): self
    {
        $resolver = new self();
        $resolver->is_cached = true;
        $resolver->route_map = $route_map;
        $resolver->request_map = $request_type_map;
        return $resolver;
    }

    /**
     * @return MiddlewareBlueprint[]
     */
    public function resolveForRoute(Route $route, ControllerAction $controller_action): array
    {
        if ($this->is_cached) {
            $map = $this->route_map[$route->getName()] ?? null;
            if (null === $map) {
                throw new LogicException(
                    "The middleware resolver is cached but has no entry for route [{$route->getName()}]."
                );
            }
            return $this->hydrateBlueprints($map);
        }

        $route_middleware = array_merge(
            $route->getMiddleware(),
            $controller_action->middleware()
        );

        if (false !== ($key = array_search('global', $route_middleware))) {
            unset($route_middleware[$key]);
        }

        array_unshift($route_middleware, 'global');

        return $this->resolve($route_middleware);
    }

    /**
     * @return MiddlewareBlueprint[]
     */
    public function resolveForRequestWithoutRoute(Request $request): array
    {
        if ($this->is_cached) {
            return $this->resolveForCachedRequest($request);
        }

        $middleware = [];

        if ($this->always_run_if_no_route_matches['global']) {
            $middleware = ['global'];
        }

        if ($request->isToApiEndpoint() && $this->always_run_if_no_route_matches['api']) {
            $middleware[] = 'api';
        } elseif ($request->isToFrontend() && $this->always_run_if_no_route_matches['frontend']) {
            $middleware[] = 'frontend';
        } elseif ($request->isToAdminArea() && $this->always_run_if_no_route_matches['admin']) {
            $middleware[] = 'admin';
        }

        if ([] === $middleware) {
            return [];
        }

        return $this->resolve($middleware);
    }

    /**
     * @param string[] $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function resolve(array $middleware = []): array
    {
        // Split out the global middleware since global middleware should always run first
        // independently of priority.
        $prepend = [];
        if (false !== ($key = array_search('global', $middleware))) {
            unset($middleware[$key]);
            $prepend = ['global'];
        }

        $blueprints = $this->parse($middleware, $this->middleware_groups);

        $blueprints = $this->sort($blueprints);

        if (!empty($prepend)) {
            $blueprints = array_merge(
                $this->parse($prepend, $this->middleware_groups),
                $blueprints
            );
        }

        return array_values(array_unique($blueprints, SORT_REGULAR));
    }

    /**
     * @param array<string>|MiddlewareBlueprint $middleware
     * @param array<string,string[]>|array<string,MiddlewareBlueprint[]> $groups
     *
     * @return MiddlewareBlueprint
     * @throws InvalidMiddleware
     */
    private function parse(array $middleware, array $groups): array
    {
        $blueprints = [];

        foreach ($middleware as $middleware_string) {
            if ($middleware_string instanceof MiddlewareBlueprint) {
                $blueprints[] = $middleware_string;
                continue;
            }

            /** @var array{0:string, 1?: string} $pieces */
            $pieces = explode(':', $middleware_string, 2);

            $middleware_id = $pieces[0];
            $replaced = $this->resolveAlias($middleware_id);

            if ($replaced) {
                $blueprints[] = new MiddlewareBlueprint(
                    $replaced,
                    isset($pieces[1]) ? explode(',', $pieces[1]) : []
                );
                continue;
            }

            if (!isset($groups[$middleware_id])) {
                throw InvalidMiddleware::becauseItsNotAnAliasOrGroup($middleware_string);
            }

            if (isset($this->unfinished_group_trace[$middleware_id])) {
                throw MiddlewareRecursion::becauseRecursionWasDetected(
                    array_keys($this->unfinished_group_trace),
                    $middleware_id
                );
            }

            $this->unfinished_group_trace[$middleware_id] = true;

            $group_middleware = $groups[$middleware_id];

            $blueprints = array_merge($blueprints, $this->parse($group_middleware, $groups));

            unset($this->unfinished_group_trace[$middleware_string]);
        }

        return $blueprints;
    }

    /**
     * @param MiddlewareBlueprint $middleware
     *
     * @return MiddlewareBlueprint
     *
     * @psalm-suppress PossiblyFalseOperand
     */
    private function sort(array $middleware): array
    {
        $sorted = $middleware;

        $success = usort($sorted, function (MiddlewareBlueprint $a, MiddlewareBlueprint $b) use ($middleware): int {
            $a_priority = $this->priorityForMiddleware($a);
            $b_priority = $this->priorityForMiddleware($b);
            $diff = $b_priority - $a_priority;

            if ($diff !== 0) {
                return $diff;
            }

            // Keep relative order from original array.
            return array_search($a, $middleware) - array_search($b, $middleware);
        });

        if (!$success) {
            throw new RuntimeException('middleware could not be sorted.');
        }

        return $sorted;
    }

    private function priorityForMiddleware(MiddlewareBlueprint $blueprint): int
    {
        $priority = array_search($blueprint->class, $this->middleware_by_increasing_priority);

        return $priority !== false ? $priority : -1;
    }

    /**
     * @param string $middleware
     * @return class-string<MiddlewareInterface>|null
     */
    private function resolveAlias(string $middleware): ?string
    {
        if (IsInterfaceString::check($middleware, MiddlewareInterface::class)) {
            return $middleware;
        }

        return $this->middleware_aliases[$middleware] ?? null;
    }

    /**
     * @param array<
     *     RoutingConfigurator::FRONTEND_MIDDLEWARE |
     *     RoutingConfigurator::ADMIN_MIDDLEWARE |
     *     RoutingConfigurator::API_MIDDLEWARE |
     *     RoutingConfigurator::GLOBAL_MIDDLEWARE
     * > $always_run
     */
    private function addAlwaysRun(array $always_run): void
    {
        Assert::allString($always_run);
        $allowed = array_keys($this->always_run_if_no_route_matches);
        foreach ($always_run as $middleware) {
            Assert::oneOf(
                $middleware,
                $allowed,
                '[%s] can not be used as middleware that is always run for non matching routes.'
            );
            $this->always_run_if_no_route_matches[$middleware] = true;
        }
    }

    /**
     * @param array<string,string> $middleware_aliases
     */
    private function addAliases(array $middleware_aliases): void
    {
        foreach ($middleware_aliases as $key => $class_string) {
            if (!IsInterfaceString::check($class_string, MiddlewareInterface::class)) {
                throw new InvalidMiddleware("Alias [$key] resolves to invalid middleware class [$class_string].");
            }
            $this->middleware_aliases[$key] = $class_string;
        }
    }

    /**
     * @param array<string,string[]> $middleware_groups
     */
    private function addMiddlewareGroups(array $middleware_groups): void
    {
        foreach ($middleware_groups as $name => $aliases_or_class_strings) {
            Assert::stringNotEmpty($name);
            Assert::allString($aliases_or_class_strings);
            if (isset($this->middleware_aliases[$name])) {
                throw new InvalidMiddleware("Middleware group and alias have the same name [$name].");
            }
        }
        foreach ($middleware_groups as $name => $aliases_or_class_strings) {
            try {
                $this->middleware_groups[$name] = $this->parse(
                    $aliases_or_class_strings,
                    $middleware_groups
                );
            } catch (MiddlewareRecursion $e) {
                throw $e->withFirstMiddleware($name);
            }
        }
    }

    /**
     * @param list<class-string<MiddlewareInterface>> $middleware_priority
     */
    private function addMiddlewarePriority(array $middleware_priority): void
    {
        Assert::allString($middleware_priority);
        $this->middleware_by_increasing_priority = array_reverse($middleware_priority);
    }

    /**
     * @param array<array{class: class-string<MiddlewareInterface>, args: array<scalar>}> $blueprints
     * @return MiddlewareBlueprint[]
     */
    private function hydrateBlueprints(array $blueprints): array
    {
        $b = [];
        foreach ($blueprints as $blueprint) {
            $b[] = MiddlewareBlueprint::from($blueprint['class'], $blueprint['args']);
        }
        return $b;
    }

    /**
     * @return MiddlewareBlueprint[]
     */
    private function resolveForCachedRequest(Request $request): array
    {
        $blueprints = $this->hydrateBlueprints($this->request_map['global'] ?? []);

        if ($request->isToApiEndpoint()) {
            $blueprints = array_merge(
                $blueprints,
                $this->hydrateBlueprints($this->request_map['api'] ?? [])
            );
        } elseif ($request->isToFrontend()) {
            $blueprints = array_merge(
                $blueprints,
                $this->hydrateBlueprints($this->request_map['frontend'] ?? [])
            );
        } elseif ($request->isToAdminArea()) {
            $blueprints = array_merge(
                $blueprints,
                $this->hydrateBlueprints($this->request_map['admin'] ?? [])
            );
        }
        return $blueprints;
    }

}