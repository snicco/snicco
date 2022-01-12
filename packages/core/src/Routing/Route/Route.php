<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Route;

use Serializable;
use RuntimeException;
use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use InvalidArgumentException;
use Snicco\Core\Controllers\FallBackController;
use Snicco\Core\Routing\Condition\ConditionBlueprint;

use function rtrim;
use function is_int;
use function implode;
use function sprintf;
use function explode;
use function is_array;
use function is_object;
use function serialize;
use function array_walk;
use function is_resource;
use function unserialize;
use function class_exists;
use function method_exists;
use function preg_match_all;
use function get_object_vars;

/**
 * @api
 */
final class Route implements Serializable
{
    
    /** @api */
    const MIDDLEWARE_DELIMITER = ':';
    
    /** @interal */
    const DELEGATE = [FallBackController::class, 'delegate'];
    
    /** @interal */
    const FALLBACK_NAME = 'sniccowp_fallback_route';
    
    /** @interal */
    const ALL_METHODS = ['GET', 'HEAD', 'POST', 'PATCH', 'PUT', 'OPTIONS', 'DELETE'];
    
    /**
     * @var array<string>
     */
    private array $methods;
    
    private string $pattern;
    
    private string $name;
    
    private string $namespace;
    
    /**
     * @var array<string,string>
     */
    private array $controller;
    
    /**
     * @var array<string>
     */
    private array $middleware = [];
    
    /**
     * @var array<string,mixed>
     */
    private array $defaults = [];
    
    /**
     * @var array<string>
     */
    private array $segment_names = [];
    
    /**
     * @var array<string>
     */
    private array $required_segments_names = [];
    
    /**
     * @var array<string>
     */
    private array $optional_segment_names = [];
    
    /**
     * @var array<string,string
     */
    private array $requirements = [];
    
    /**
     * @var array<ConditionBlueprint>
     */
    private array $conditions = [];
    
    private function __construct()
    {
    }
    
    /** @interal */
    public static function create(
        string $pattern,
        $controller,
        string $name = null,
        array $methods = self::ALL_METHODS,
        string $namespace = ''
    ) :Route {
        $route = new self();
        
        $route->setPattern($pattern);
        $route->setNamespace($namespace);
        $route->setController($controller);
        $route->setMethods($methods);
        $route->setName($name);
        
        return $route;
    }
    
    public function getMethods() :array
    {
        return $this->methods;
    }
    
    public function getRequirements() :array
    {
        return $this->requirements;
    }
    
    public function getRequiredSegmentNames() :array
    {
        return $this->required_segments_names;
    }
    
    public function getOptionalSegmentNames() :array
    {
        return $this->optional_segment_names;
    }
    
    public function getSegmentNames() :array
    {
        return $this->segment_names;
    }
    
    public function getName() :string
    {
        return $this->name;
    }
    
    public function getPattern() :string
    {
        return $this->pattern;
    }
    
    public function getController() :array
    {
        return $this->controller;
    }
    
    public function getMiddleware() :array
    {
        return $this->middleware;
    }
    
    public function getConditions() :array
    {
        return $this->conditions;
    }
    
    public function getDefaults() :array
    {
        return $this->defaults;
    }
    
    public function requirements(array $requirements) :Route
    {
        $this->addRequirements($requirements);
        return $this;
    }
    
    public function defaults(array $defaults) :Route
    {
        foreach ($defaults as $key => $value) {
            $this->addDefaultValue($key, $value);
        }
        return $this;
    }
    
    public function condition(string $condition, ...$args) :Route
    {
        $b = new ConditionBlueprint($condition, $args);
        
        Assert::keyNotExists(
            $this->conditions,
            $condition,
            sprintf(
                'Condition [%s] was added twice to route [%s].',
                $condition,
                $this->getName()
            )
        );
        
        $this->conditions[$b->class()] = $b;
        return $this;
    }
    
    /**
     * @param  string|array<string>  $middleware
     */
    public function middleware($middleware) :Route
    {
        foreach ((array) $middleware as $m) {
            $this->addMiddleware($m);
        }
        
        return $this;
    }
    
    /** @interal */
    public function serialize() :string
    {
        return serialize(get_object_vars($this));
    }
    
    /** @interal */
    public function unserialize($data) :void
    {
        $data = unserialize($data);
        
        if ( ! is_array($data)) {
            throw new RuntimeException(
                sprintf(
                    "Route could not be deserialized with data: [%s]",
                    var_export($data, true)
                )
            );
        }
        
        foreach ($data as $property_name => $value) {
            $this->{$property_name} = $value;
        }
    }
    
    public function matchesOnlyWithTrailingSlash() :bool
    {
        return Str::endsWith($this->pattern, '/');
    }
    
    /**
     * @param  string|array<string>  $segment_names
     */
    public function requireAlpha($segment_names, bool $allow_uppercase = false) :Route
    {
        $s = [];
        
        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = $allow_uppercase ? '[a-zA-Z]+' : '[a-z]+';
        }
        
        $this->requirements($s);
        return $this;
    }
    
    /**
     * @param  string|array<string>  $segment_names
     */
    public function requireAlphaNum($segment_names, bool $allow_uppercase = false) :Route
    {
        $s = [];
        
        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = $allow_uppercase ? '[a-zA-Z0-9]+' : '[a-z0-9]+';
        }
        
        $this->requirements($s);
        return $this;
    }
    
    /**
     * @param  string|array<string>  $segment_names
     */
    public function requireNum($segment_names) :Route
    {
        $s = [];
        
        foreach ((array) $segment_names as $segment_name) {
            $s[$segment_name] = '[0-9]+';
        }
        
        $this->requirements($s);
        return $this;
    }
    
    public function requireOneOf(string $segment_name, array $values) :Route
    {
        array_walk($values, function ($value) {
            $value = is_int($value) ? (string) $value : $value;
            Assert::string($value);
        });
        
        $arr = [$segment_name => implode('|', $values)];
        
        $this->addRequirements($arr);
        
        return $this;
    }
    
    /**
     * @param  string|array  $controller
     */
    private function setController($controller) :void
    {
        $controller = is_array($controller)
            ? $controller
            : explode('@', $controller);
        
        if ( ! isset($controller[1])) {
            $controller[1] = '__invoke';
        }
        
        Assert::count($controller, 2, 'Expected controller array to have a class and a method.');
        Assert::stringNotEmpty($controller[0], 'Expected controller class to be a string.');
        Assert::stringNotEmpty($controller[1], 'Expected controller method to be a string.');
        
        if ( ! class_exists($controller[0])) {
            $controller[0] = ! empty($this->namespace)
                ? $this->namespace.'\\'.$controller[0]
                : $controller[0];
            
            if ( ! class_exists($controller[0])) {
                throw new InvalidArgumentException(
                    sprintf('Controller class [%s] does not exist.', $controller[0]),
                );
            }
        }
        
        if ( ! method_exists($controller[0], $controller[1])) {
            throw new InvalidArgumentException(
                sprintf('The method [%s::%s] is not callable.', $controller[0], $controller[1]),
            );
        }
        
        $this->controller = $controller;
    }
    
    private function setMethods(array $methods) :void
    {
        Assert::allInArray($methods, self::ALL_METHODS);
        $this->methods = $methods;
    }
    
    private function setName(?string $name) :void
    {
        if ( ! empty($name)) {
            Assert::stringNotEmpty($name);
            Assert::notStartsWith($name, '.');
        }
        else {
            $name = $this->pattern.':'.implode('@', $this->controller);
        }
        $this->name = $name;
    }
    
    private function setNamespace(string $namespace) :void
    {
        $this->namespace = rtrim($namespace, '\\');
    }
    
    private function setPattern(string $pattern) :void
    {
        Assert::startsWith($pattern, '/', 'Expected route pattern to start with /.');
        Assert::notStartsWith($pattern, '//');
        $this->pattern = $pattern;
        
        // @see https://regexr.com/6cn0d
        preg_match_all('/[^{]\w+(?=\??})/', $pattern, $names);
        Assert::uniqueValues(
            $names[0],
            'Route segment names have to be unique but %s of them %s duplicated.'
        );
        
        $this->segment_names = $names[0];
        
        preg_match_all('/[^{]\w+(?=})/', $pattern, $required_names);
        $this->required_segments_names = $required_names[0];
        
        preg_match_all('/[^{]\w+(?=\?})/', $pattern, $optional_names);
        $this->optional_segment_names = $optional_names[0];
    }
    
    private function addRequirements(array $requirements) :void
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
                "Requirement for segment [$segment] can not be overwritten."
            );
            $this->requirements[$segment] = $regex;
        }
    }
    
    private function addDefaultValue(string $key, $value) :void
    {
        if (is_object($value) || is_resource($value)) {
            throw new InvalidArgumentException("A route default value has to be a primitive type.");
        }
        
        $this->defaults[$key] = $value;
    }
    
    private function addMiddleware(string $m) :void
    {
        $middleware_id = Str::before($m, self::MIDDLEWARE_DELIMITER);
        Assert::keyNotExists(
            $this->middleware,
            $middleware_id,
            "Middleware [$middleware_id] added twice to route [$this->name]."
        );
        $this->middleware[$middleware_id] = $m;
    }
    
}