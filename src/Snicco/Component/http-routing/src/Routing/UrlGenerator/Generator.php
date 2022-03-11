<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\Admin\AdminArea;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteParameter;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\StrArr\Str;

use function array_diff_key;
use function array_key_exists;
use function array_map;
use function array_merge;
use function gettype;
use function is_int;
use function is_string;
use function ltrim;
use function parse_str;
use function preg_match;
use function rtrim;
use function str_replace;
use function strpos;
use function trim;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class Generator implements UrlGenerator
{
    private Routes $routes;

    private UrlGenerationContext $context;

    private AdminArea $admin_area;

    private UrlEncoder $encoder;

    public function __construct(
        Routes $routes,
        UrlGenerationContext $request_context,
        AdminArea $admin_area,
        UrlEncoder $encoder = null
    ) {
        $this->routes = $routes;
        $this->context = $request_context;
        $this->encoder = $encoder ?? new RFC3986Encoder();
        $this->admin_area = $admin_area;
    }

    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $https = null): string
    {
        if ($this->isValidUrl($path)) {
            return $path;
        }

        return $this->generate($path, $extra, $type, $https);
    }

    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string
    {
        try {
            return $this->toRoute('login', $arguments, $type, true);
        } catch (RouteNotFound $e) {
        }

        try {
            return $this->toRoute('auth.login', $arguments, $type, true);
        } catch (RouteNotFound $e) {
        }

        try {
            return $this->toRoute('framework.auth.login', $arguments, $type, true);
        } catch (RouteNotFound $e) {
        }

        return $this->to($this->admin_area->loginPath(), $arguments, $type, true);
    }

    public function toRoute(
        string $name,
        array $arguments = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $https = null
    ): string {
        $route = $this->routes->getByName($name);

        $trailing = $route->matchesOnlyWithTrailingSlash();

        $route_path = $route->getPattern();

        if (Str::startsWith($route_path, (string) $this->admin_area->urlPrefix())) {
            [$route_path, $q] = $this->admin_area->rewriteForUrlGeneration($route_path);
            $arguments = array_merge($arguments, $q);
        }

        $required_segments = $route->getRequiredSegmentNames();
        $optional_segments = $route->getOptionalSegmentNames();
        $requirements = $route->getRequirements();

        $arguments = $this->toStringValues($arguments);

        // All arguments that are not route segments will be used as query arguments.
        $extra = array_diff_key($arguments, array_flip($route->getSegmentNames()));

        // Replace required segments
        $route_path = $this->replaceSegments($required_segments, $requirements, $arguments, $route_path, $name,);

        // Replace optional segments
        $route_path = $this->replaceSegments(
            $optional_segments,
            $requirements,
            $arguments,
            $route_path,
            $name,
            true
        );

        if ($trailing) {
            $route_path = rtrim($route_path, '/') . '/';
        }

        return $this->generate($route_path, $extra, $type, $https);
    }

    private function isValidUrl(string $path): bool
    {
        return preg_match('#^(\#|//|https?://|(mailto|tel|sms):)#', $path) && false !== filter_var(
            $path,
            FILTER_VALIDATE_URL
        );
    }

    /**
     * @param array<string,int|string> $extra
     */
    private function generate(
        string $path,
        array $extra = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $secure = null,
        bool $encode_path = true
    ): string {
        [$path, $existing_query, $existing_fragment] = $this->splitUserPath($path);

        $extra = $this->toStringValues($extra);

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

        $target = $path . $query_string . $fragment;

        if (self::ABSOLUTE_PATH === $type) {
            return $target;
        }

        $scheme = $this->requiredScheme($secure);

        return $scheme . '://' . $this->withHostAndPort($target, $scheme);
    }

    /**
     * @return array{0:string, 1:string, 2:string}
     */
    private function splitUserPath(string $path_with_query_and_fragment): array
    {
        $query_pos = strpos($path_with_query_and_fragment, '?');
        $fragment_pos = strpos($path_with_query_and_fragment, '#');

        $path = $path_with_query_and_fragment;
        $query_string = '';
        $fragment = '';

        if (false !== $query_pos) {
            $path = Str::substr($path_with_query_and_fragment, 0, $query_pos);
            $query_string = Str::substr($path_with_query_and_fragment, $query_pos + 1);
        }

        if (false !== $fragment_pos) {
            $path = Str::substr($path_with_query_and_fragment, 0, $fragment_pos);
            $fragment = Str::substr($path_with_query_and_fragment, $fragment_pos + 1);
        }

        return [$path, $query_string, $fragment];
    }

    private function formatPath(string $path): string
    {
        $path = ltrim($path, '/');

        return '/' . $path;
    }

    /**
     * @param array<string,string> $extra_query_args
     */
    private function buildQueryString(array $extra_query_args, string $existing_query_string): string
    {
        parse_str($existing_query_string, $existing_query);

        /** @var array<string,string> $existing_query */
        $query = array_merge($existing_query, $extra_query_args);

        if ([] === $query) {
            return '';
        }

        return '?' . $this->encoder->encodeQuery($query);
    }

    private function buildFragment(string $existing_fragment, string $extra_fragment): string
    {
        if (! empty($extra_fragment)) {
            return '#' . $this->encoder->encodeFragment($extra_fragment);
        }

        if (! empty($existing_fragment)) {
            return '#' . $this->encoder->encodeFragment($existing_fragment);
        }

        return '';
    }

    private function requiredScheme(?bool $secure): string
    {
        if (null === $secure) {
            return $this->context->httpsByDefault() ? 'https' : 'http';
        }

        return $secure ? 'https' : 'http';
    }

    private function withHostAndPort(string $valid_url_or_path, string $scheme): string
    {
        $port = '';

        if ('https' === $scheme) {
            if (443 !== $this->context->httpsPort()) {
                $port = ':' . (string) $this->context->httpsPort();
            }
        } elseif ('http' === $scheme) {
            if (80 !== $this->context->httpPort()) {
                $port = ':' . (string) $this->context->httpPort();
            }
        }

        return $this->context->host() . $port . $valid_url_or_path;
    }

    /**
     * @param string[]             $segments
     * @param array<string,string> $requirements
     * @param array<string,string> $provided_arguments
     */
    private function replaceSegments(
        array $segments,
        array $requirements,
        array $provided_arguments,
        string $route_path,
        string $name,
        bool $optional = false
    ): string {
        foreach ($segments as $segment) {
            $has_value = array_key_exists($segment, $provided_arguments);

            if (! $has_value && ! $optional) {
                throw BadRouteParameter::becauseRequiredParameterIsMissing($segment, $name);
            }

            $replacement = $provided_arguments[$segment] ?? '';

            if ($has_value && array_key_exists($segment, $requirements)) {
                $pattern = $requirements[$segment];

                if (! preg_match('/^' . $pattern . '$/', $replacement)) {
                    throw BadRouteParameter::becauseRegexDoesntMatch($replacement, $segment, $pattern, $name);
                }
            }

            $search = $optional ? '{' . $segment . '?}' : '{' . $segment . '}';

            $route_path = str_replace($search, $replacement, $route_path);
        }

        // Make sure there are no left over trailing slashes for blank optional values.
        return rtrim($route_path, '/');
    }

    /**
     * @param array<string,mixed> $extra
     *
     * @return array<string,string>
     */
    private function toStringValues(array $extra): array
    {
        return array_map(function ($value): string {
            if (! is_string($value) && ! is_int($value)) {
                throw new InvalidArgumentException(
                    sprintf('Replacements must be string or integer. Got [%s].', gettype($value))
                );
            }

            return (string) $value;
        }, $extra);
    }
}
