<?php

declare(strict_types=1);

namespace Snicco\Routing\FastRoute;

use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Routing\Route;
use Snicco\Contracts\ConvertsToUrl;
use Snicco\Contracts\RouteUrlGenerator;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class FastRouteUrlGenerator implements RouteUrlGenerator
{
    
    /** @see https://regexr.com/5s536 */
    public const matching_pattern = '/(?<optional>(?:\[\/)?(?<required>{{.+?}}+)(?:\]+)?)/i';
    /** @see https://regexr.com/5s533 */
    public const double_curly_brackets = '/(?<=\/)(?<opening_bracket>\{)|(?<closing_bracket>\}(?=(\/|\[\/|\]|$)))/';
    
    private array $url_cache = [];
    
    private RouteCollectionInterface $routes;
    
    public function __construct(RouteCollectionInterface $routes)
    {
        $this->routes = $routes;
    }
    
    /**
     * @throws ConfigurationException
     */
    public function to(string $name, array $arguments) :string
    {
        if (isset($this->url_cache[$name])) {
            return $this->url_cache[$name];
        }
        
        $route = $this->findRoute($name);
        
        if ($route instanceof ConvertsToUrl) {
            return $route->toUrl($arguments);
        }
        
        $regex = $this->routeRegex($route);
        
        $url = ((new FastRouteSyntax()))->convert($route);
        
        $url = $this->convertToDoubleCurlyBrackets($url);
        
        $url = $this->replaceRouteSegmentsWithValues($url, $regex, $arguments);
        
        if ($arguments === []) {
            $this->url_cache[$name] = $url;
        }
        
        return $url;
    }
    
    private function findRoute(string $name) :Route
    {
        $route = $this->routes->findByName($name);
        
        if ( ! $route) {
            throw new ConfigurationException(
                'There is no named route with the name: '.$name.' registered.'
            );
        }
        
        return $route;
    }
    
    private function routeRegex(Route $route) :array
    {
        return Arr::flattenOnePreserveKeys($route->getRegex() ?? []);
    }
    
    private function convertToDoubleCurlyBrackets(string $url)
    {
        return preg_replace_callback(self::double_curly_brackets, function ($matches) {
            if ($open = $matches['opening_bracket'] ?? null) {
                return $open.$open;
            }
            if ($closing = $matches['closing_bracket'] ?? null) {
                return $closing.$closing;
            }
        }, $url);
    }
    
    private function replaceRouteSegmentsWithValues(string $url, array $route_regex, array $values)
    {
        return preg_replace_callback(
            self::matching_pattern,
            function ($matches) use ($values, $route_regex) {
                $required = $this->stripBrackets($matches['required']);
                $optional = $this->isOptional($matches['optional']);
                $value = Arr::get($values, $required, '');
                
                $value = is_int($value) ? strval($value) : $value;
                
                if ($value === '' && ! $optional) {
                    throw new ConfigurationException(
                        'Required route segment: {'.$required.'} missing.'
                    );
                }
                
                if ($constraint = Arr::get($route_regex, $required)) {
                    $this->satisfiesSegmentRegex($constraint, $value, $required);
                }
                
                return ($optional) ? '/'.$value : $value;
            },
            $url
        );
    }
    
    private function stripBrackets(string $pattern) :string
    {
        $pattern = Str::between($pattern, '{{', '}}');
        return Str::before($pattern, ':');
    }
    
    private function isOptional(string $pattern) :bool
    {
        return Str::startsWith($pattern, '[/{') && Str::endsWith($pattern, [']', '}']);
    }
    
    private function satisfiesSegmentRegex($pattern, $value, $segment)
    {
        $regex_constraint = '/'.$pattern.'/';
        
        if ( ! preg_match($regex_constraint, $value)) {
            throw new ConfigurationException(
                'The provided value ['
                .$value
                .'] is not valid for the route: [foo]'
                .
                PHP_EOL
                .'The value for {'
                .$segment
                .'} needs to have the regex pattern: '
                .$pattern
                .'.'
            );
        }
    }
    
}