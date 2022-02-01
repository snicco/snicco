<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Snicco\Component\HttpRouting\Exception\RequestHasNoType;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use stdClass;

use function filter_var;
use function func_get_args;
use function in_array;
use function is_array;
use function is_bool;
use function strtok;
use function strtoupper;
use function trim;

use const FILTER_VALIDATE_BOOLEAN;

final class Request implements ServerRequestInterface
{

    /**
     * @var @internal
     */
    const TYPE_ATTRIBUTE = '_request_type';

    /**
     * @var @internal
     */
    const TYPE_FRONTEND = 1;

    /**
     * @var @internal
     */
    const TYPE_ADMIN_AREA = 2;

    /**
     * @var @internal
     */
    const TYPE_API = 3;

    private ServerRequestInterface $psr_request;

    public function __construct(ServerRequestInterface $psr_request)
    {
        $this->psr_request = $psr_request;
    }

    final public static function fromPsr(ServerRequestInterface $psr_request): Request
    {
        return new self($psr_request);
    }

    final public function withRoutingResult(RoutingResult $route): Request
    {
        return $this->withAttribute('_routing_result', $route);
    }

    public function withAttribute($name, $value): Request
    {
        return $this->new($this->psr_request->withAttribute($name, $value));
    }

    // path + query + fragment

    public function new(ServerRequestInterface $new_psr_request): Request
    {
        return new self($new_psr_request);
    }

    // scheme + host + path

    /**
     * @return false|string
     */
    final public function userAgent()
    {
        return substr($this->getHeaderLine('User-Agent'), 0, 500);
    }

    // scheme + host + path + query + fragment

    public function getHeaderLine($name): string
    {
        return $this->psr_request->getHeaderLine($name);
    }

    final public function fullRequestTarget(): string
    {
        $fragment = $this->getUri()->getFragment();

        return ($fragment !== '')
            ? $this->getRequestTarget() . '#' . $fragment
            : $this->getRequestTarget();
    }

    public function getUri(): UriInterface
    {
        return $this->psr_request->getUri();
    }

    public function getRequestTarget(): string
    {
        return $this->psr_request->getRequestTarget();
    }

    final public function url(): string
    {
        return preg_replace('/\?.*/', '', (string)$this->getUri());
    }

    final public function cookie(string $name, ?string $default = null)
    {
        return Arr::get($this->getCookieParams(), $name, $default);
    }

    public function getCookieParams(): array
    {
        return $this->psr_request->getCookieParams();
    }

    final function routeIs(string $pattern): bool
    {
        $route = $this->routingResult()->route();

        if (!$route) {
            return false;
        }

        return Str::is($pattern, $route->getName());
    }

    final function routingResult(): RoutingResult
    {
        return $this->getAttribute('_routing_result', RoutingResult::noMatch());
    }

    public function getAttribute($name, $default = null)
    {
        return $this->psr_request->getAttribute($name, $default);
    }

    /**
     * @note The full url is not urldecoded here.
     */
    final function fullUrlIs(string ...$patterns): bool
    {
        $url = $this->fullUrl();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    final public function fullUrl(): string
    {
        return $this->getUri()->__toString();
    }

    final function pathIs(string ...$patterns): bool
    {
        $path = $this->decodedPath();

        foreach ($patterns as $pattern) {
            if (Str::is('/' . ltrim($pattern, '/'), $path)) {
                return true;
            }
        }

        return false;
    }

    final function decodedPath(): string
    {
        $path = $this->path();
        return implode(
            '/',
            array_map(function ($part) {
                return rawurldecode(strtr($part, ['%2F' => '%252F']));
            }, explode('/', $path))
        );
    }

    final function path(): string
    {
        return $this->getUri()->getPath();
    }

    /**
     * A request is considered secure when the scheme is set to "https".
     * If your site runs behind a reverse proxy you have to make sure that your reverse proxy is
     * configured correctly for setting the HTTP_X_FORWARDED_PROTO header. It's purposely not
     * possible to configure trusted proxies because if this is not done configured at the server
     * level the entire WP application will misbehave anyway.
     *
     * @see ServerRequestCreator::createUriFromArray()
     */
    final public function isSecure(): bool
    {
        return 'https' === $this->getUri()->getScheme();
    }

    /**
     * @throws RequestHasNoType
     */
    final public function isToFrontend(): bool
    {
        return self::TYPE_FRONTEND === $this->getType();
    }

    /**
     * @throws RequestHasNoType
     */
    private function getType(): int
    {
        $type = $this->getAttribute(self::TYPE_ATTRIBUTE, false);

        if (!is_int($type)) {
            throw RequestHasNoType::becauseTheTypeIsNotAnInteger($type);
        }

        if ($type < 1 || $type > 3) {
            throw RequestHasNoType::becauseTheRangeIsNotCorrect($type);
        }

        return $type;
    }

    /**
     * @throws RequestHasNoType
     */
    final public function isToAdminArea(): bool
    {
        return self::TYPE_ADMIN_AREA === $this->getType();
    }

    /**
     * @throws RequestHasNoType
     */
    final public function isToApiEndpoint(): bool
    {
        return self::TYPE_API === $this->getType();
    }

    final public function ip(): ?string
    {
        return $this->server('REMOTE_ADDR');
    }

    /**
     * @param null|string $default
     *
     * @psalm-param 'default'|null $default
     */
    final public function server(string $key, ?string $default = null)
    {
        return Arr::get($this->getServerParams(), $key, $default);
    }

    public function getServerParams(): array
    {
        return $this->psr_request->getServerParams();
    }

    final public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    private function isMethod(string $method): bool
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }

    public function getMethod(): string
    {
        return $this->psr_request->getMethod();
    }

    final public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    final public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    final public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    final public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    final public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    final public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    final public function isReadVerb(): bool
    {
        return $this->isMethodSafe();
    }

    final public function isMethodSafe(): bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
    }

    final public function isAjax(): bool
    {
        return $this->isXmlHttpRequest();
    }

    final public function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
    }

    final public function isSendingJson(): bool
    {
        return Str::contains($this->getHeaderLine('Content-Type'), ['/json', '+json']);
    }

    final public function isExpectingJson(): bool
    {
        $accepts = $this->acceptableContentTypes(false);

        return Str::contains($accepts, ['/json', '+json']);
    }

    /**
     * @return string|string[]
     *
     * @psalm-return array<string>|string
     */
    final public function acceptableContentTypes(bool $as_array = true)
    {
        return $as_array ? $this->getHeader('Accept') : $this->getHeaderLine('Accept');
    }

    public function getHeader($name): array
    {
        return $this->psr_request->getHeader($name);
    }

    final public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    final public function accepts(string $content_type): bool
    {
        $accepts = $this->acceptableContentTypes();

        return $this->matchesType($content_type, $accepts);
    }

    private function matchesType(string $match_against, array $content_types): bool
    {
        if ($content_types === []) {
            return true;
        }

        foreach ($content_types as $content_type) {
            if ($content_type === '*/*' || $content_type === '*') {
                return true;
            }

            if ($content_type === strtok($match_against, '/') . '/*') {
                return true;
            }
        }

        return in_array($match_against, $content_types);
    }

    final public function acceptsOneOf(array $content_types): bool
    {
        $accepts = $this->acceptableContentTypes();

        foreach ($content_types as $content_type) {
            if ($this->matchesType($content_type, $accepts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param null|string $default
     *
     * @psalm-param 'default'|null $default
     */
    final public function query(string $key = null, ?string $default = null)
    {
        $query = $this->getQueryParams();

        if (!$key) {
            return $query;
        }

        return Arr::get($query, $key, $default);
    }

    public function getQueryParams(): array
    {
        return $this->psr_request->getQueryParams();
    }

    final public function queryString(): string
    {
        $qs = $this->getUri()->getQuery();

        while (Str::endsWith($qs, '&') || Str::endsWith($qs, '=')) {
            $qs = mb_substr($qs, 0, -1);
        }

        return $qs;
    }

    final public function body(string $name = null, $default = null)
    {
        return $this->post($name, $default);
    }

    final public function post(string $name = null, $default = null)
    {
        if (!$name) {
            return $this->getParsedBody() ?? [];
        }

        return Arr::get($this->getParsedBody(), $name, $default);
    }

    public function getParsedBody()
    {
        return $this->psr_request->getParsedBody();
    }

    /**
     * @param null|string $key
     * @return bool|false
     *
     */
    final public function boolean(?string $key, bool $default = false): bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * This method supports "*" as wildcards in the key.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    final public function input(?string $key = null, $default = null)
    {
        $all = $this->all();

        if (null === $key) {
            return $all;
        }

        return Arr::dataGet($all, $key, $default);
    }

    final public function all(): array
    {
        return $this->inputSource();
    }

    private function inputSource(): array
    {
        $input = in_array($this->realMethod(), ['GET', 'HEAD'])
            ? $this->getQueryParams()
            : $this->getParsedBody();

        return (array)$input;
    }

    final public function realMethod()
    {
        return Arr::get($this->getServerParams(), 'REQUEST_METHOD', 'GET');
    }

    /**
     * This method does not support * WILDCARDS
     *
     * @psalm-param 'product.description'|'product.name' $keys
     */
    final public function only(string $keys): array
    {
        $results = [];

        $input = $this->all();

        $placeholder = new stdClass;

        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            $value = Arr::dataGet($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Determine if the request contains a non-empty value for an input item.
     *
     * @param string|string[] $keys
     */
    final public function filled($keys): bool
    {
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $value) {
            if ($this->isEmptyString($value)) {
                return false;
            }
        }

        return true;
    }

    private function isEmptyString(string $key): bool
    {
        $value = $this->input($key);

        return !is_bool($value) && !is_array($value) && trim((string)$value) === '';
    }

    /**
     * This method does not support * WILDCARDS
     *
     * @psalm-param 'product.name' $keys
     */
    final public function except(string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->all();

        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * @param string|string[] $keys
     *
     * @psalm-param array{0: 'foo.bax'|'name'|'surname', 1: 'email'|'foo.baz'|'password'}|string $keys
     */
    final public function hasAny($keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $input = $this->all();

        return Arr::hasAny($input, $keys);
    }

    /**
     * Will return falls if any of the provided keys is missing.
     *
     * @param string|string[] $key
     *
     * @psalm-param array{0: 'foo.bax'|'surname', 1: 'foo.baz'|'password'}|string $key
     */
    final public function missing($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        return !$this->has($keys);
    }

    /**
     * @param array|string $key
     *
     * @psalm-param 'products'|'products.0.name'|array $key
     */
    final public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (!Arr::has($input, $value)) {
                return false;
            }
        }

        return true;
    }

    public function withProtocolVersion($version): Request
    {
        return $this->new($this->psr_request->withProtocolVersion($version));
    }

    public function withHeader($name, $value): Request
    {
        return $this->new($this->psr_request->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value): Request
    {
        return $this->new($this->psr_request->withAddedHeader($name, $value));
    }

    public function withoutHeader($name): Request
    {
        return $this->new($this->psr_request->withoutHeader($name));
    }

    public function withBody(StreamInterface $body): Request
    {
        return $this->new($this->psr_request->withBody($body));
    }

    public function withRequestTarget($requestTarget): Request
    {
        return $this->new($this->psr_request->withRequestTarget($requestTarget));
    }

    public function withMethod($method): Request
    {
        return $this->new($this->psr_request->withMethod($method));
    }

    public function withUri(UriInterface $uri, $preserveHost = false): Request
    {
        return $this->new($this->psr_request->withUri($uri, $preserveHost));
    }

    public function withQueryParams(array $query): Request
    {
        return $this->new($this->psr_request->withQueryParams($query));
    }

    public function withCookieParams(array $cookies): Request
    {
        return $this->new($this->psr_request->withCookieParams($cookies));
    }

    public function withoutAttribute($name): Request
    {
        return $this->new($this->psr_request->withoutAttribute($name));
    }

    public function withParsedBody($data): Request
    {
        return $this->new($this->psr_request->withParsedBody($data));
    }

    public function withUploadedFiles(array $uploadedFiles): Request
    {
        return $this->new($this->psr_request->withUploadedFiles($uploadedFiles));
    }

    public function getProtocolVersion(): string
    {
        return $this->psr_request->getProtocolVersion();
    }

    public function getHeaders(): array
    {
        return $this->psr_request->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->psr_request->hasHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->psr_request->getBody();
    }

    public function getUploadedFiles(): array
    {
        return $this->psr_request->getUploadedFiles();
    }

    public function getAttributes(): array
    {
        return $this->psr_request->getAttributes();
    }

}