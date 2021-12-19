<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Support\Str;
use Snicco\Core\Support\Url;
use Snicco\Core\Support\UrlParser;
use Snicco\Core\Contracts\UrlEncoder;
use Snicco\Core\Contracts\HasCustomRoutePath;
use Snicco\Core\Contracts\UrlGeneratorInterface;
use Snicco\Core\Contracts\RouteCollectionInterface;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;
use Snicco\Core\ExceptionHandling\Exceptions\BadRouteParameter;

final class UrlGenerator implements UrlGeneratorInterface
{
    
    private const FRAGMENT_KEY = '_fragment';
    
    /**
     * @var RouteCollectionInterface
     */
    private $routes;
    
    /**
     * @var UrlGenerationContext
     */
    private $context;
    
    /**
     * @var UrlEncoder
     */
    private $encoder;
    
    public function __construct(RouteCollectionInterface $routes, UrlGenerationContext $request_context, UrlEncoder $encoder)
    {
        $this->routes = $routes;
        $this->context = $request_context;
        $this->encoder = $encoder;
    }
    
    public function toRoute(string $name, array $arguments = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string
    {
        $route = $this->routes->findByName($name);
        
        if ( ! $route) {
            throw RouteNotFound::name($name);
        }
        
        if ($route instanceof HasCustomRoutePath) {
            $route_path = $route->toPath($this->encoder, $arguments);
        }
        else {
            $route_path = $route->getUrl();
        }
        
        $required_segments = UrlParser::requiredSegments($route_path);
        $optional_segments = UrlParser::optionalSegments($route_path);
        $requirements = $route->getRegex();
        
        // All arguments that are not route segments will be used as query arguments.
        $extra = array_diff_key($arguments, array_flip(UrlParser::segmentNames($route_path)));
        
        $route_path = $this->replaceSegments(
            $required_segments,
            $requirements,
            $arguments,
            $route_path,
            $name,
        );
        $route_path = $this->replaceSegments(
            $optional_segments,
            $requirements,
            $arguments,
            $route_path,
            $name,
            true
        );
        
        return $this->generate($route_path, $extra, $type, $secure);
    }
    
    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null) :string
    {
        if (Url::isValidAbsolute($path)) {
            return $path;
        }
        
        return $this->generate($path, $extra, $type, $secure);
    }
    
    public function secure(string $path, array $extra = []) :string
    {
        return $this->to($path, $extra, self::ABSOLUTE_URL, true);
    }
    
    public function canonical() :string
    {
        return $this->generate(
            $this->context->path(),
            [],
            self::ABSOLUTE_URL,
            null,
            false
        );
    }
    
    public function full() :string
    {
        return $this->context->uriAsString();
    }
    
    public function previous(string $fallback = '/') :string
    {
        $referer = $this->context->referer();
        
        if ( ! $referer) {
            return $this->generate($fallback, [], self::ABSOLUTE_URL);
        }
        
        return $referer;
    }
    
    private function generate(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null, bool $encode_path = true) :string
    {
        [$path, $existing_query, $existing_fragment] = $this->splitUserPath($path);
        
        $path = $encode_path
            ? $this->encoder->encodePath($path)
            : $path;
        
        $path = $this->formatPath($path);
        
        $extra_fragment = '';
        if (isset($extra[self::FRAGMENT_KEY])) {
            $extra_fragment = trim($extra[self::FRAGMENT_KEY], '#');
            unset($extra[self::FRAGMENT_KEY]);
        }
        
        $query_string = $this->buildQueryString($extra, $existing_query);
        $fragment = $this->buildFragment($existing_fragment, $extra_fragment);
        
        if ($type != self::ABSOLUTE_URL && $this->needsUpgradeToAbsoluteHttps($secure)) {
            $type = self::ABSOLUTE_URL;
        }
        
        $target = $path.$query_string.$fragment;
        
        if ($type === self::ABSOLUTE_PATH) {
            return $target;
        }
        
        $scheme = $this->requiredScheme($secure);
        
        return $scheme.'://'.$this->formatType($target, $type, $scheme);
    }
    
    private function formatType(string $valid_url_or_path, int $type, string $scheme) :string
    {
        $is_url = Str::startsWith($valid_url_or_path, 'http');
        
        if ($is_url) {
            if ($type === self::ABSOLUTE_URL) {
                return $valid_url_or_path;
            }
            
            $path = Str::after($valid_url_or_path, $this->context->getHost());
            return '/'.ltrim($path, '/');
        }
        
        if ($type === self::ABSOLUTE_PATH) {
            return $valid_url_or_path;
        }
        
        $port = '';
        
        if ($scheme === 'https') {
            if ($this->context->getHttpsPort() !== 443) {
                $port = ':'.$this->context->getHttpsPort();
            }
        }
        elseif ($scheme === 'http') {
            if ($this->context->getHttpPort() !== 80) {
                $port = ':'.$this->context->getHttpPort();
            }
        }
        
        return $this->context->getHost().$port.$valid_url_or_path;
    }
    
    private function requiredScheme(?bool $secure = null) :string
    {
        if ( ! is_null($secure)) {
            return $secure ? 'https' : 'http';
        }
        
        if ($this->context->shouldForceHttps()) {
            return 'https';
        }
        
        return $this->context->getScheme();
    }
    
    private function buildQueryString(array $extra_query_args, string $existing_query_string) :string
    {
        parse_str($existing_query_string, $existing_query);
        
        $query = array_merge((array) $existing_query, $extra_query_args);
        
        if ($query === []) {
            return '';
        }
        
        return '?'.$this->encoder->encodeQuery($query);
    }
    
    private function formatPath(string $path) :string
    {
        $path = trim($path, '/');
        
        $path = '/'.$path;
        
        if ( ! $this->context->withTrailingSlashes()) {
            return $path;
        }
        
        if (Str::endsWith($path, '.php')) {
            return $path;
        }
        return $path.'/';
    }
    
    private function splitUserPath(string $path_with_query_and_fragment) :array
    {
        $query_pos = strpos($path_with_query_and_fragment, '?');
        $fragment_pos = strpos($path_with_query_and_fragment, '#');
        
        $path = $path_with_query_and_fragment;
        $query_string = '';
        $fragment = '';
        
        if ($query_pos !== false) {
            $path = substr($path_with_query_and_fragment, 0, $query_pos);
            $query_string = substr($path_with_query_and_fragment, $query_pos + 1);
        }
        
        if ($fragment_pos !== false) {
            $path = substr($path_with_query_and_fragment, 0, $fragment_pos);
            $fragment = substr($path_with_query_and_fragment, $fragment_pos + 1);
        }
        
        return [
            $path,
            $query_string,
            $fragment,
        ];
    }
    
    private function buildFragment(string $existing_fragment, string $extra_fragment) :string
    {
        if ( ! empty($extra_fragment)) {
            return '#'.$this->encoder->encodeFragment($extra_fragment);
        }
        
        if ( ! empty($existing_fragment)) {
            return '#'.$this->encoder->encodeFragment($existing_fragment);
        }
        
        return '';
    }
    
    private function needsUpgradeToAbsoluteHttps(?bool $secure) :bool
    {
        if ($secure === false) {
            return false;
        }
        
        if ($this->context->isSecure()) {
            return false;
        }
        
        if ($this->context->shouldForceHttps()) {
            return true;
        }
        
        return (bool) $secure;
    }
    
    private function replaceSegments(array $segments, array $requirements, array $provided_arguments, string $route_path, string $name, bool $optional = false) :string
    {
        foreach ($segments as $segment) {
            $has_value = array_key_exists($segment, $provided_arguments);
            
            if ( ! $has_value && ! $optional) {
                throw BadRouteParameter::becauseRequiredParameterIsMissing(
                    $segment,
                    $name
                );
            }
            
            $replacement = $provided_arguments[$segment] ?? '';
            
            if ($has_value && array_key_exists($segment, $requirements)) {
                $pattern = $requirements[$segment];
                
                if ( ! preg_match('/^'.$pattern.'$/', $replacement)) {
                    throw BadRouteParameter::becauseRegexDoesntMatch(
                        $replacement,
                        $segment,
                        $pattern,
                        $name
                    );
                }
            }
            
            $search = $optional ? '{'.$segment.'?}' : '{'.$segment.'}';
            
            $route_path = str_replace($search, $replacement, $route_path);
        }
        
        return $route_path;
    }
    
}