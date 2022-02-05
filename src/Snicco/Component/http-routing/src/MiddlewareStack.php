<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Exception\InvalidMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Webmozart\Assert\Assert;

use function array_merge;
use function class_exists;
use function explode;

/**
 * The middleware stack is responsible for parsing and normalized all middleware for a request.
 *
 * @internal
 * @psalm-internal Snicco\Component\HttpRouting
 * @todo All the resolving of route middleware should maybe be done before the routes get compiled into the cache.
 */
final class MiddlewareStack
{

    /**
     * @api
     */
    const MIDDLEWARE_DELIMITER = ':';

    /**
     * @api
     */
    const ARGUMENT_SEPARATOR = ',';

    /**
     * @var array<string,string[]>
     */
    private const CORE_GROUPS = [
        RoutingConfigurator::FRONTEND_MIDDLEWARE => [],
        RoutingConfigurator::ADMIN_MIDDLEWARE => [],
        RoutingConfigurator::API_MIDDLEWARE => [],
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [],
    ];

    /**
     * @var array<string,string[]>
     */
    private array $user_provided_groups = [];

    /**
     * @var array<string, class-string<MiddlewareInterface>>
     */
    private array $middleware_aliases = [];

    /**
     * @var list<class-string<MiddlewareInterface>>
     */
    private array $middleware_by_increasing_priority = [];

    /**
     * @var string[]
     */
    private array $run_always_on_mismatch = [];

    private bool $middleware_disabled = false;

    /**
     * @param string[]|class-string<MiddlewareInterface>[] $middleware_to_always_run_on_non_route_match
     */
    public function __construct(array $middleware_to_always_run_on_non_route_match = [])
    {
        Assert::allString($middleware_to_always_run_on_non_route_match);
        foreach ($middleware_to_always_run_on_non_route_match as $middleware) {
            Assert::keyExists(
                self::CORE_GROUPS,
                $middleware,
                '[%s] can not be used as middleware that is always run for non matching routes.'
            );
            $this->run_always_on_mismatch[$middleware] = $middleware;
        }
    }

    /**
     * @param string[] $route_middleware
     * @return MiddlewareBlueprint[]
     */
    public function createWithRouteMiddleware(array $route_middleware): array
    {
        if ($this->middleware_disabled) {
            return [];
        }

        $middleware = [RoutingConfigurator::GLOBAL_MIDDLEWARE];

        foreach ($route_middleware as $index => $name) {
            if ($this->isMiddlewareGroup($name)) {
                unset($route_middleware[$index]);
                $middleware = array_merge($middleware, $this->middlewareGroups()[$name]);
            } else {
                $middleware[] = $name;
            }
        }

        return $this->create($middleware);
    }

    /**
     * @return MiddlewareBlueprint[]
     */
    public function createForRequestWithoutRoute(Request $request): array
    {
        return $this->create($this->middlewareForNonMatchingRequest($request));
    }

    /**
     * @param string $group
     * @param string[] $middlewares
     */
    public function withMiddlewareGroup(string $group, array $middlewares): void
    {
        $this->user_provided_groups[$group] = array_merge(
            $this->user_provided_groups[$group] ?? [],
            $middlewares
        );
    }

    /**
     * @param list<class-string<MiddlewareInterface>> $middleware_priority
     */
    public function middlewarePriority(array $middleware_priority): void
    {
        $this->middleware_by_increasing_priority = array_reverse($middleware_priority);
    }

    /**
     * @param array<string, class-string<MiddlewareInterface> > $route_middleware_aliases
     */
    public function middlewareAliases(array $route_middleware_aliases): void
    {
        $this->middleware_aliases = array_merge(
            $this->middleware_aliases,
            $route_middleware_aliases
        );
    }

    public function disableAllMiddleware(): void
    {
        $this->middleware_disabled = true;
    }

    private function isMiddlewareGroup(string $alias_or_class): bool
    {
        return isset($this->middlewareGroups()[$alias_or_class]);
    }

    /**
     * @return array<string,string[]>
     */
    private function middlewareGroups(): array
    {
        return array_merge(self::CORE_GROUPS, $this->user_provided_groups);
    }

    /**
     * @param string[] $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function create(array $middleware = []): array
    {
        // Split out the global middleware since global middleware should always run first
        // independently of priority.
        $prepend = [];
        if (false !== ($key = array_search(RoutingConfigurator::GLOBAL_MIDDLEWARE, $middleware))) {
            unset($middleware[$key]);
            $prepend = [RoutingConfigurator::GLOBAL_MIDDLEWARE];
        }

        $middleware = $this->sort(
            $this->parseAliasesAndArguments($middleware)
        );

        $middleware = array_merge(
            $this->parseAliasesAndArguments($prepend),
            $middleware
        );

        return $this->unique($middleware);
    }

    /**
     * @note Middleware with the highest priority comes first in the array
     *
     * @param list<MiddlewareBlueprint> $middleware
     *
     * @return list<MiddlewareBlueprint>
     *
     * @psalm-suppress PossiblyFalseOperand
     *
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
            throw new RuntimeException('middleware could not be sorted');
        }

        return $sorted;
    }

    private function priorityForMiddleware(MiddlewareBlueprint $blueprint): int
    {
        $priority = array_search($blueprint->class(), $this->middleware_by_increasing_priority);

        return $priority !== false ? $priority : -1;
    }

    /**
     * @param string[] $middleware
     *
     * @return list<MiddlewareBlueprint>
     * @throws InvalidMiddleware
     *
     * @psalm-suppress PossiblyFalseArgument
     */
    private function parseAliasesAndArguments(array $middleware): array
    {
        $blueprints = [];

        foreach ($middleware as $middleware_string) {
            /** @var array{0:string, 1?: string} $pieces */
            $pieces = explode(self::MIDDLEWARE_DELIMITER, $middleware_string, 2);

            if (isset($pieces[1])) {
                $args = explode(self::ARGUMENT_SEPARATOR, $pieces[1]);

                $blueprints[] = new MiddlewareBlueprint(
                    $this->replaceMiddlewareAlias($pieces[0]),
                    $args
                );

                continue;
            }

            if ($this->isMiddlewareGroup($pieces[0])) {
                $blueprints = array_merge(
                    $blueprints,
                    $this->parseAliasesAndArguments($this->middlewareGroups()[$pieces[0]])
                );
                continue;
            }

            $blueprints[] = new MiddlewareBlueprint(
                $this->replaceMiddlewareAlias($pieces[0])
            );
        }

        return $blueprints;
    }

    /**
     * @param string $middleware
     * @return class-string<MiddlewareInterface>
     */
    private function replaceMiddlewareAlias(string $middleware): string
    {
        if (class_exists($middleware) && Reflection::isInterface($middleware, MiddlewareInterface::class)) {
            /** @var class-string<MiddlewareInterface> $middleware */
            return $middleware;
        }

        if (isset($this->middleware_aliases[$middleware])) {
            $middleware = $this->middleware_aliases[$middleware];
            if (Reflection::isInterface($middleware, MiddlewareInterface::class)) {
                return $middleware;
            }
            throw InvalidMiddleware::incorrectInterface($middleware);
        }

        throw InvalidMiddleware::becauseTheAliasDoesNotExist($middleware);
    }

    /**
     * @param MiddlewareBlueprint[] $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function unique(array $middleware): array
    {
        return array_values(array_unique($middleware, SORT_REGULAR));
    }

    /**
     * @return string[]
     */
    private function middlewareForNonMatchingRequest(Request $request): array
    {
        if ($this->middleware_disabled) {
            return [];
        }

        $middleware = [];

        if (in_array(RoutingConfigurator::GLOBAL_MIDDLEWARE, $this->run_always_on_mismatch, true)) {
            $middleware = [RoutingConfigurator::GLOBAL_MIDDLEWARE];
        }

        if ($request->isToApiEndpoint()) {
            if (in_array(
                RoutingConfigurator::API_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::API_MIDDLEWARE]);
            }

            return $middleware;
        }

        if ($request->isToFrontend()) {
            if (in_array(
                RoutingConfigurator::FRONTEND_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::FRONTEND_MIDDLEWARE]);
            }

            return $middleware;
        }

        if ($request->isToAdminArea()) {
            if (in_array(
                RoutingConfigurator::ADMIN_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::ADMIN_MIDDLEWARE]);
            }

            return $middleware;
        }

        return $middleware;
    }

}