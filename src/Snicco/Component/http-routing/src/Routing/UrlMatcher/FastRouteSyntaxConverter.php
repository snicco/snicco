<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\StrArr\Str;

use function count;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace_callback;
use function rtrim;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strlen;

/**
 * Converts our custom route pattern syntax to something that FastRoute understands.
 * We use our own syntax because
 * a) we are independent of FastRoute.
 * b) FastRoute is rather verbose with optional segments.
 *
 * @interal
 */
final class FastRouteSyntaxConverter
{

    public function convert(Route $route): string
    {
        $route_url = $route->getPattern();
        $match_only_trailing = false;
        $is_optional = Str::contains($route_url, '?}');

        if (Str::endsWith($route_url, '?}/')) {
            $match_only_trailing = true;
        }

        $url = $this->convertOptionalSegments(
            $route_url,
            $route->getOptionalSegmentNames(),
            $match_only_trailing
        );

        foreach ($route->getRequirements() as $param_name => $pattern) {
            $url = $this->addCustomRegexToSegments($param_name, $pattern, $url);
        }

        if ($match_only_trailing && $is_optional) {
            $url = $this->ensureOptionalRouteCanMatchWithTrailingSlash($url);
        }

        return $url;
    }

    /**
     * @param string[] $optional_segment_names
     */
    private function convertOptionalSegments(
        string $url_pattern,
        array $optional_segment_names,
        bool $match_only_trailing
    ): string {
        if (!count($optional_segment_names)) {
            return $url_pattern;
        }

        foreach ($optional_segment_names as $name) {
            $replace_with = $match_only_trailing ? '/[{' . $name . '}]' : '[/{' . $name . '}]';

            $url_pattern = str_replace('/{' . $name . '?}', $replace_with, $url_pattern);
        }

        while ($this->hasMultipleOptionalSegments(rtrim($url_pattern, '/'))) {
            $this->combineOptionalSegments($url_pattern);
        }

        return $url_pattern;
    }

    private function hasMultipleOptionalSegments(string $url_pattern): bool
    {
        $count = preg_match_all('/(?<=\[).*?(?=])/', $url_pattern, $matches);

        return $count > 1;
    }

    private function combineOptionalSegments(string &$url_pattern): void
    {
        preg_match('/(\[(.*?)])/', $url_pattern, $matches);

        if (!isset($matches[0])) {
            return;
        }

        $first = $matches[0];

        $before = Str::beforeFirst($url_pattern, $first);
        $after = Str::afterLast($url_pattern, $first);

        $url_pattern = $before . rtrim($first, ']') . rtrim($after, '/') . ']';
    }

    private function addCustomRegexToSegments(string $param_name, string $pattern, string $url): string
    {
        $regex = $this->replaceEscapedForwardSlashes($pattern);

        $pattern = sprintf("/(%s(?=\\}))/", preg_quote($param_name, '/'));

        $url = preg_replace_callback($pattern, function (array $match) use ($regex) {
            if (!isset($match[0])) {
                return $regex;
            }
            return $match[0] . ':' . $regex;
        }, $url, 1);

        if (null == $url) {
            throw new RuntimeException("preg_replace_callback returned an error for url [$url].");
        }

        return rtrim($url, '/');
    }

    /**
     * @note Fast Route uses unescaped forward slashes and wraps the entire regex in ~ chars.
     */
    private function replaceEscapedForwardSlashes(string $regex): string
    {
        return str_replace('\\/', '/', $regex);
    }

    private function ensureOptionalRouteCanMatchWithTrailingSlash(string $url): string
    {
        $l1 = strlen($url);
        $url = rtrim($url, ']');
        $l2 = strlen($url);
        $url .= '[/]' . str_repeat(']', $l1 - $l2);
        return $url;
    }

}