<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionException;
use RuntimeException;
use Snicco\Component\HttpRouting\Controller\ControllerAction;
use Snicco\Component\HttpRouting\Exception\InvalidMiddleware;
use Snicco\Component\HttpRouting\Exception\MiddlewareRecursion;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Reflector;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
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
    /**
     * @var string
     */
    public const MIDDLEWARE_DELIMITER = ':';

    /**
     * @var string
     */
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
     * @var array<'admin'|'api'|'frontend'|'global',bool>
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
    private array $unfinished_group_trace = [];

    private bool $is_cached = false;

    /**
     * @var array<string, array< array{class: class-string<MiddlewareInterface>, args: array<string>}>>
     */
    private array $route_map = [];

    /**
     * @var array<'admin'|'api'|'frontend'|'global', array<array{class: class-string<MiddlewareInterface>, args: array<string>}>>
     */
    private array $request_map = [];

    /**
     * @param array<
     *     RoutingConfigurator::FRONTEND_MIDDLEWARE |
     *     RoutingConfigurator::ADMIN_MIDDLEWARE |
     *     RoutingConfigurator::API_MIDDLEWARE |
     *     RoutingConfigurator::GLOBAL_MIDDLEWARE
     * > $always_run_if_no_route_matches
     * @param array<string,class-string<MiddlewareInterface>>               $middleware_aliases
     * @param array<string,array<class-string<MiddlewareInterface>|string>> $middleware_groups
     * @param list<class-string<MiddlewareInterface>>                       $middleware_priority
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
     * @param array<string, array< array{class: class-string<MiddlewareInterface>, args: array<string>}>>                            $route_map
     * @param array<'admin'|'api'|'frontend'|'global', array< array{class: class-string<MiddlewareInterface>, args: array<string>}>> $request_type_map
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
                    sprintf('The middleware resolver is cached but has no entry for route [%s].', $route->getName())
                );
            }

            return $this->hydrateBlueprints($map);
        }

        $route_middleware = array_merge($route->getMiddleware(), $controller_action->middleware());

        if (false !== ($key = array_search('global', $route_middleware, true))) {
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
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     * @return array{
     *     route_map: array<string, list<array{class: class-string<MiddlewareInterface>, args: array<string>}>>,
     *     request_map: array{
     *          api: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>,
     *          frontend: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>,
     *          admin: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>,
     *          global: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>
     *      }
     * }
     */
    public function createMiddlewareCache(Routes $routes, ContainerInterface $container): array
    {
        $route_map = [];

        foreach ($routes as $route) {
            $action = new ControllerAction($route->getController(), $container);
            $middlewares = $this->resolveForRoute($route, $action);
            $route_map[$name = $route->getName()] = [];
            foreach ($middlewares as $middleware) {
                $route_map[$name][] = $middleware->asArray();
            }
        }

        $request_map = [
            'api' => [],
            'frontend' => [],
            'admin' => [],
            'global' => [],
        ];

        foreach (array_keys($request_map) as $type) {
            if (! $this->always_run_if_no_route_matches[$type]) {
                continue;
            }

            foreach ($this->resolve([$type]) as $blueprint) {
                $request_map[$type][] = $blueprint->asArray();
            }
        }

        return [
            'route_map' => $route_map,
            'request_map' => $request_map,
        ];
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
        if (false !== ($key = array_search('global', $middleware, true))) {
            unset($middleware[$key]);
            $prepend = ['global'];
        }

        $blueprints = $this->parse($middleware, $this->middleware_groups);

        $blueprints = $this->sort($blueprints);

        if (! empty($prepend)) {
            $blueprints = [...$this->parse($prepend, $this->middleware_groups), ...$blueprints];
        }

        return array_values(array_unique($blueprints, SORT_REGULAR));
    }

    /**
     * @param array<MiddlewareBlueprint|string>                          $middleware
     * @param array<string,MiddlewareBlueprint[]>|array<string,string[]> $groups
     *
     * @throws InvalidMiddleware
     *
     * @return list<MiddlewareBlueprint>
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

            if (null !== $replaced) {
                $blueprints[] = new MiddlewareBlueprint(
                    $replaced,
                    isset($pieces[1]) ? explode(',', $pieces[1]) : []
                );

                continue;
            }

            if (! isset($groups[$middleware_id])) {
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

            $blueprints = [...$blueprints, ...$this->parse($group_middleware, $groups)];

            unset($this->unfinished_group_trace[$middleware_string]);
        }

        return $blueprints;
    }

    /**
     * @param list<MiddlewareBlueprint> $middleware
     *
     * @return list<MiddlewareBlueprint>
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

            if (0 !== $diff) {
                return $diff;
            }

            // Keep relative order from original array.
            return array_search($a, $middleware, true) - array_search($b, $middleware, true);
        });

        if (! $success) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('middleware could not be sorted.');
            // @codeCoverageIgnoreEnd
        }

        return $sorted;
    }

    private function priorityForMiddleware(MiddlewareBlueprint $blueprint): int
    {
        $priority = array_search($blueprint->class, $this->middleware_by_increasing_priority, true);

        return false !== $priority ? $priority : -1;
    }

    /**
     * @return class-string<MiddlewareInterface>|null
     */
    private function resolveAlias(string $middleware): ?string
    {
        try {
            Reflector::assertInterfaceString($middleware, MiddlewareInterface::class);

            return $middleware;
        } catch (InvalidArgumentException $e) {
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
            Reflector::assertInterfaceString(
                $class_string,
                MiddlewareInterface::class,
                "Alias [{$key}] resolves to invalid middleware class-string [%2\$s].\nExpected: [%1\$s]."
            );
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
                throw new InvalidMiddleware(sprintf('Middleware group and alias have the same name [%s].', $name));
            }
        }

        foreach ($middleware_groups as $name => $aliases_or_class_strings) {
            try {
                $this->middleware_groups[$name] = $this->parse($aliases_or_class_strings, $middleware_groups);
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
     * @param array<array{class: class-string<MiddlewareInterface>, args: array<string>}> $blueprints
     *
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
            $blueprints = array_merge($blueprints, $this->hydrateBlueprints($this->request_map['api'] ?? []));
        } elseif ($request->isToFrontend()) {
            $blueprints = array_merge($blueprints, $this->hydrateBlueprints($this->request_map['frontend'] ?? []));
        } elseif ($request->isToAdminArea()) {
            $blueprints = array_merge($blueprints, $this->hydrateBlueprints($this->request_map['admin'] ?? []));
        }

        return $blueprints;
    }
}
