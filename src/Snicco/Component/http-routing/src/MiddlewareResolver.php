<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Exception\InvalidMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_merge;
use function array_reverse;
use function class_exists;
use function explode;

/**
 * @todo All the resolving of route middleware should maybe be done before the routes get compiled into the cache.
 */
final class MiddlewareResolver
{

    public const MIDDLEWARE_DELIMITER = ':';
    public const ARGUMENT_SEPARATOR = ',';

    /**
     * @var array<string,string[]>
     */
    private const SPECIAL_GROUPS = [
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
     * @var array<string,true>
     */
    private $unfinished_group_trace = [];

    /**
     * @param string[] $always_run
     * @param array<string,class-string<MiddlewareInterface>> $middleware_aliases
     * @param array<string,array<string|class-string<MiddlewareInterface>>> $middleware_groups
     * @param list<class-string<MiddlewareInterface>> $middleware_priority
     */
    public function __construct(
        array $always_run = [],
        array $middleware_aliases = [],
        array $middleware_groups = [],
        array $middleware_priority = []
    ) {
        $this->addAlwaysRun($always_run);
        $this->addAliases($middleware_aliases);
        $this->addMiddlewareGroups($middleware_groups);
        $this->addMiddlewarePriority($middleware_priority);
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

        $middleware = array_merge($middleware, $route_middleware);

        return $this->create($middleware);
    }

    /**
     * @return MiddlewareBlueprint[]
     */
    public function createForRequestWithoutRoute(Request $request): array
    {
        return $this->create($this->middlewareForNonMatchingRequest($request));
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
        return array_merge(self::SPECIAL_GROUPS, $this->user_provided_groups);
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

            if (isset($this->middleware_aliases[$middleware_string])) {
                $blueprints[] = new MiddlewareBlueprint($this->middleware_aliases[$middleware_string]);
                continue;
            }

            if (Reflection::isInterface($middleware_string, MiddlewareInterface::class)) {
                $blueprints[] = new MiddlewareBlueprint($middleware_string);
                continue;
            }

            if (!$this->isMiddlewareGroup($middleware_string)) {
                throw new RuntimeException('Invalid middleware');
            }

            if (isset($this->unfinished_group_trace[$middleware_string])) {
                throw InvalidMiddleware::becauseRecursionWasDetected(
                    array_keys($this->unfinished_group_trace),
                    $middleware_string
                );
            }

            $this->unfinished_group_trace[$middleware_string] = true;

            $group_middleware = $this->middlewareGroups()[$middleware_string];

            $blueprints = array_merge($blueprints, $this->parseAliasesAndArguments($group_middleware));

            unset($this->unfinished_group_trace[$middleware_string]);
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

    private function addAlwaysRun(array $always_run): void
    {
        Assert::allString($always_run);
        foreach ($always_run as $middleware) {
            Assert::keyExists(
                self::SPECIAL_GROUPS,
                $middleware,
                '[%s] can not be used as middleware that is always run for non matching routes.'
            );
            $this->run_always_on_mismatch[$middleware] = $middleware;
        }
    }

    /**
     * @param array<string,class-string<MiddlewareInterface>> $middleware_aliases
     */
    private function addAliases(array $middleware_aliases): void
    {
        foreach ($middleware_aliases as $key => $class_string) {
            if (!Reflection::isInterface($class_string, MiddlewareInterface::class)) {
                throw new InvalidMiddleware("Alias [$key] resolves to invalid middleware class [$class_string].");
            }
            if (isset($this->middleware_aliases[$key])) {
                throw new InvalidMiddleware("Duplicate middleware alias [$key].");
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

            if (isset($this->user_provided_groups[$name])) {
                throw new InvalidMiddleware("Duplicate middleware group name [$name]");
            }
            if (isset($this->middleware_aliases[$name])) {
                throw new InvalidMiddleware("Middleware group and alias have the same name [$name].");
            }
            $this->user_provided_groups[$name] = $aliases_or_class_strings;
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

}