<?php

declare(strict_types=1);

namespace Snicco\Routing\FastRoute;

use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Routing\Route;
use Snicco\Support\UrlParser;

class FastRouteSyntax
{
    
    public function convert(Route $route) :string
    {
        $route_url = $route->getUrl();
        
        $with_trailing = Str::endsWith($route_url, '/') && Str::doesNotEndWith($route_url, '?}/');
        
        $url = $this->convertOptionalSegments($route_url);
        
        foreach ($route->getRegex() as $regex) {
            $url = $this->addCustomRegexToSegments($regex, $url);
        }
        
        $url = $with_trailing ? Url::addTrailing($url) : $url;
        
        if ($route->needsTrailingSlash()) {
            $url = $this->ensureRouteOnlyMatchesWithTrailingSlash($url, $route);
        }
        
        return $url;
    }
    
    private function convertOptionalSegments(string $url_pattern) :string
    {
        $optionals = UrlParser::getOptionalSegments($url_pattern);
        
        if ( ! count($optionals)) {
            return $url_pattern;
        }
        
        foreach ($optionals as $optional) {
            $optional = preg_quote($optional, '/');
            
            $pattern = sprintf("#(%s)#", $optional);
            
            $url_pattern = preg_replace_callback($pattern, function ($match) {
                $cleaned_match = Str::between($match[0], '{', '?');
                
                return sprintf("[/{%s}]", $cleaned_match);
            }, $url_pattern, 1);
        }
        
        while ($this->hasMultipleOptionalSegments(rtrim($url_pattern, '/'))) {
            $this->combineOptionalSegments($url_pattern);
        }
        
        return $url_pattern;
    }
    
    private function addCustomRegexToSegments(array $regex, string $url) :string
    {
        $regex = $this->replaceEscapedForwardSlashes($regex);
        
        $segments = UrlParser::segments($url);
        
        $segments = $this->segmentsWithCustomRegex($segments, $regex);
        
        foreach ($segments as $segment) {
            $pattern = sprintf("/(%s(?=\\}))/", preg_quote($segment, '/'));
            
            $url = preg_replace_callback($pattern, function ($match) use ($regex) {
                return $match[0].':'.$regex[$match[0]];
            }, $url, 1);
        }
        
        return rtrim($url, '/');
    }
    
    private function hasMultipleOptionalSegments(string $url_pattern) :bool
    {
        $count = preg_match_all('/(?<=\[).*?(?=])/', $url_pattern, $matches);
        
        return $count > 1;
    }
    
    private function combineOptionalSegments(string &$url_pattern)
    {
        preg_match('/(\[(.*?)])/', $url_pattern, $matches);
        
        $first = $matches[0];
        
        $before = Str::before($url_pattern, $first);
        $after = Str::afterLast($url_pattern, $first);
        
        $url_pattern = $before.rtrim($first, ']').rtrim($after, '/').']';
    }
    
    private function segmentsWithCustomRegex($segments, $regex) :array
    {
        return array_filter($segments, function ($segment) use ($regex) {
            return isset($regex[$segment]);
        });
    }
    
    private function ensureRouteOnlyMatchesWithTrailingSlash($url, Route $route) :string
    {
        foreach ($route->getSegmentNames() as $segment) {
            $url = $this->addCustomRegexToSegments([$segment => '[^\/]+\/?'], $url);
        }
        
        return Str::replaceFirst('[/', '/[', $url);
    }
    
    /**
     * @note Fast Route uses unescaped forward slashes and wraps the entire regex in ~ chars.
     */
    private function replaceEscapedForwardSlashes(array $regex)
    {
        return str_replace('\\/', '/', $regex);
    }
    
}