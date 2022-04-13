<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Route;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Controller\DelegateResponseController;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Snicco\Component\HttpRouting\Routing\Condition\ConditionBlueprint;
use Snicco\Component\HttpRouting\Routing\Condition\RouteCondition;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function array_map;
use function class_exists;
use function explode;
use function get_object_vars;
use function implode;
use function is_array;
use function is_int;
use function is_scalar;
use function method_exists;
use function preg_match_all;
use function rtrim;
use function sprintf;

final class Route
{
    /**
     * @interal
     *
     * @var string[]|class-string<DelegateResponseController>[]
     */
    public const DELEGATE = [DelegateResponseController::class, '__invoke'];

    /**
     * @interal
     *
     * @var string
     */
    public const FALLBACK_NAME = 'snicco_fallback_route';

    /**
     * @interal
     *
     * @var string[]
     */
    public const ALL_METHODS = ['GET', 'HEAD', 'POST', 'PATCH', 'PUT', 'OPTIONS', 'DELETE'];

    /**
     * @var string[]
     */
    private array $methods = [];

    private string $pattern;

    private string $name;

    private string $namespace;

    /**
     * @var array{0:class-string, 1:string}
     */
    private array $controller;

    /**
     * @var array<string,string>
     */
    private array $middleware = [];

    /**
     * @var array<string,mixed>
     */
    private array $defaults = [];

    /**
     * @var string[]
     */
    private array $segment_names = [];

    /**
     * @var string[]
     */
    private array $required_segments_names = [];

    /**
     * @var string[]
     */
    private array $optional_segment_names = [];

    /**
     * @var array<string,string>
     */
    private array $requirements = [];

    /**
     * @var ConditionBlueprint[]
     */
    private array $conditions = [];

    /**
     * @param array{0: class-string, 1: string}|class-string|string $controller
     * @param string[]                                              $methods
     */
    private function __construct(
        string $pattern,
        $controller,
        string $name = null,
        array $methods = self::ALL_METHODS,
        string $namespace = ''
    ) {
        $this->setPattern($pattern);
        $this->setNamespace($namespace);
        $this->setController($controller);
        $this->setMethods($methods);
        $this->setName($name);
    }

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $property_name => $value) {
            $this->{$property_name} = $value;
        }
    }

    /**
     * @interal
     *
     * @param array{0: class-string, 1: string}|class-string|string $controller
     * @param string[]                                              $methods
     */
    public static function create(
        string $pattern,
        $controller,
        string $name = null,
        array $methods = self::ALL_METHODS,
        string $namespace = ''
    ): Route {
        return new self($pattern, $controller, $name, $methods, $namespace);
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return array<string,string>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * @return string[]
     */
    public function getRequiredSegmentNames(): array
    {
        return $this->required_segments_names;
    }

    /**
     * @return string[]
     */
    public function getOptionalSegmentNames(): array
    {
        return $this->optional_segment_names;
    }

    /**
     * @return string[]
     */
    public function getSegmentNames(): array
    {
        return $this->segment_names;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array{0:class-string, 1:string}
     */
    public function getController(): array
    {
        return $this->controller;
    }

    /**
     * @return string[]
     */
    public function getMiddleware(): array
    {
        return array_values($this->middleware);
    }

    /**
     * @return ConditionBlueprint[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @param scalar $args
     *
     * @psalm-param  class-string<RouteCondition>|'!' $condition
     */
    public function condition(string $condition, ...$args): Route
    {
        $b = new ConditionBlueprint($condition, $args);

        Assert::keyNotExists(
            $this->conditions,
            $condition,
            sprintf('Condition [%s] was added twice to route [%s].', $condition, $this->name)
        );

        $this->conditions[$b->class] = $b;

        return $this;
    }

    /**
     * @param array<string,array<scalar>|scalar> $defaults
     */
    public function defaults(array $defaults): Route
    {
        foreach ($defaults as $key => $value) {
            $this->addDefaultValue($key, $value);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param array<string>|string $middleware
     */
    public function middleware($middleware): Route
    {
        foreach ((array) $middleware as $m) {
            $this->addMiddleware($m);
        }

        return $this;
    }

    public function matchesOnlyWithTrailingSlash(): bool
    {
        return Str::endsWith($this->pattern, '/');
    }

    /**
     * @param array<string>|string $segment_names
     */
    public function requireAlpha($segment_names, bool $allow_uppercase = false): Route
    {
        $s = [];

        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = $allow_uppercase ? '[a-zA-Z]+' : '[a-z]+';
        }

        $this->requirements($s);

        return $this;
    }

    public function requirements(array $requirements): Route
    {
        $this->addRequirements($requirements);

        return $this;
    }

    /**
     * @param array<string>|string $segment_names
     */
    public function requireAlphaNum($segment_names, bool $allow_uppercase = false): Route
    {
        $s = [];

        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = $allow_uppercase ? '[a-zA-Z0-9]+' : '[a-z0-9]+';
        }

        $this->requirements($s);

        return $this;
    }

    /**
     * @param array<string>|string $segment_names
     */
    public function requireNum($segment_names): Route
    {
        $s = [];

        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = '[0-9]+';
        }

        $this->requirements($s);

        return $this;
    }

    /**
     * @param array<int|string> $values
     */
    public function requireOneOf(string $segment_name, array $values): Route
    {
        $values = array_map(function ($value): string {
            $value = is_int($value) ? (string) $value : $value;
            Assert::string($value);

            return $value;
        }, $values);

        $arr = [
            $segment_name => implode('|', $values),
        ];

        $this->addRequirements($arr);

        return $this;
    }

    private function setPattern(string $pattern): void
    {
        Assert::startsWith($pattern, '/', 'Expected route pattern to start with /.');
        Assert::notStartsWith($pattern, '//');
        $this->pattern = $pattern;

        // @see https://regexr.com/6cn0d
        preg_match_all('#[^{]\w+(?=\??})#', $pattern, $names);

        if (! empty($names[0])) {
            Assert::uniqueValues($names[0], 'Route segment names have to be unique but %s of them %s duplicated.');
            $this->segment_names = $names[0];
        }

        preg_match_all('#[^{]\w+(?=})#', $pattern, $required_names);

        if (! empty($required_names[0])) {
            $this->required_segments_names = $required_names[0];
        }

        preg_match_all('#[^{]\w+(?=\?})#', $pattern, $optional_names);

        if (! empty($optional_names[0])) {
            $this->optional_segment_names = $optional_names[0];
        }
    }

    private function setNamespace(string $namespace): void
    {
        $this->namespace = rtrim($namespace, '\\');
    }

    /**
     * @param array{0: class-string, 1: string}|class-string|string $controller
     */
    private function setController($controller): void
    {
        $controller = is_array($controller)
            ? $controller
            : explode('@', $controller);

        if (! isset($controller[0])) {
            throw new InvalidArgumentException('Expected controller array to have a class and a method.');
        }

        if (! isset($controller[1])) {
            $controller[1] = '__invoke';
        }

        Assert::count($controller, 2, 'Expected controller array to have a class and a method.');
        Assert::stringNotEmpty($controller[0], 'Expected controller class to be a string.');
        Assert::stringNotEmpty($controller[1], 'Expected controller method to be a string.');

        if (! class_exists($controller[0])) {
            $controller[0] = empty($this->namespace)
                ? $controller[0]
                : $this->namespace . '\\' . $controller[0];

            if (! class_exists($controller[0])) {
                throw new InvalidArgumentException(
                    sprintf('Controller class [%s] does not exist.', $controller[0]),
                );
            }
        }

        if (! method_exists($controller[0], $controller[1])) {
            throw new InvalidArgumentException(
                sprintf('The method [%s::%s] is not callable.', $controller[0], $controller[1]),
            );
        }

        $this->controller = $controller;
    }

    /**
     * @param string[] $methods
     */
    private function setMethods(array $methods): void
    {
        Assert::allInArray($methods, self::ALL_METHODS);
        $this->methods = $methods;
    }

    private function setName(?string $name): void
    {
        if (! empty($name)) {
            Assert::stringNotEmpty($name);
            Assert::notStartsWith($name, '.');
            Assert::notContains(
                $name,
                ' ',
                sprintf('Route name for route [%s] should not contain whitespaces.', $name)
            );
        } else {
            $name = $this->pattern . ':' . implode('@', $this->controller);
        }

        $this->name = $name;
    }

    /**
     * @param array<scalar>|scalar $value
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    private function addDefaultValue(string $key, $value): void
    {
        if (! is_scalar($value) && ! is_array($value)) {
            throw new InvalidArgumentException('A route default value has to be a scalar or an array of scalars.');
        }

        $this->defaults[$key] = $value;
    }

    private function addMiddleware(string $m): void
    {
        $middleware_id = Str::beforeFirst($m, MiddlewareResolver::MIDDLEWARE_DELIMITER);
        Assert::keyNotExists(
            $this->middleware,
            $middleware_id,
            sprintf('Middleware [%s] added twice to route [%s].', $middleware_id, $this->name)
        );
        $this->middleware[$middleware_id] = $m;
    }

    private function addRequirements(array $requirements): void
    {
        foreach ($requirements as $segment => $regex) {
            Assert::stringNotEmpty($segment);
            Assert::stringNotEmpty($regex);
            Assert::inArray(
                $segment,
                $this->segment_names,
                'Expected one of the valid segment names: [%2$s]. Got: [%s].'
            );
            Assert::keyNotExists(
                $this->requirements,
                $segment,
                sprintf('Requirement for segment [%s] can not be overwritten.', $segment)
            );
            $this->requirements[$segment] = $regex;
        }
    }
}
