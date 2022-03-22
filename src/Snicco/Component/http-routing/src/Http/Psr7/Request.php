<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use BadMethodCallException;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use stdClass;
use Webmozart\Assert\Assert;

use function array_map;
use function explode;
use function filter_var;
use function gettype;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function rawurldecode;
use function sprintf;
use function strtok;
use function strtoupper;
use function strtr;
use function substr;
use function trim;

use const FILTER_VALIDATE_BOOLEAN;

final class Request implements ServerRequestInterface
{
    /**
     * @var string
     */
    public const TYPE_FRONTEND = 'frontend';

    /**
     * @var string
     */
    public const TYPE_ADMIN_AREA = 'admin';

    /**
     * @var string
     */
    public const TYPE_API = 'api';

    private ServerRequestInterface $psr_request;

    /**
     * @var "admin"|"api"|"frontend"
     */
    private string $type;

    /**
     * @param self::TYPE_ADMIN_AREA|self::TYPE_API|self::TYPE_FRONTEND $type
     */
    public function __construct(ServerRequestInterface $psr_request, string $type = self::TYPE_FRONTEND)
    {
        Assert::oneOf($type, [self::TYPE_FRONTEND, self::TYPE_ADMIN_AREA, self::TYPE_API]);

        if ($psr_request instanceof Request && $psr_request->type !== $type) {
            throw new LogicException(
                sprintf('Cant change request type from [%s] to [%s].', $psr_request->type, $type)
            );
        }

        $this->psr_request = $psr_request;
        $this->type = $type;
    }

    /**
     * @param mixed $value
     *
     * @return never
     */
    public function __set(string $name, $value)
    {
        throw new BadMethodCallException(
            sprintf(sprintf('Cannot set undefined property [%s] on immutable class [%%s]', $name), self::class)
        );
    }

    /**
     * @param self::TYPE_ADMIN_AREA|self::TYPE_API|self::TYPE_FRONTEND $type
     */
    public static function fromPsr(ServerRequestInterface $psr_request, string $type = self::TYPE_FRONTEND): Request
    {
        if ($psr_request instanceof Request) {
            return $psr_request;
        }

        return new self($psr_request, $type);
    }

    public function userAgent(): ?string
    {
        $user_agent = (string) substr($this->getHeaderLine('user-agent'), 0, 500);

        if ('' === $user_agent) {
            return null;
        }

        return $user_agent;
    }

    /**
     * Returns schema + host + path.
     */
    public function url(): string
    {
        $full = (string) $this->getUri();

        return Str::pregReplace($full, '/\?.*/', '');
    }

    /**
     * Returns the value of a received cookie or $default if no cookie with the
     * given name exists.
     *
     * If multiple cookie headers have been sent for $name only the first one
     * will be returned.
     */
    public function cookie(string $name, ?string $default = null): ?string
    {
        /** @var array<string,non-empty-list<string>|string> $cookies */
        $cookies = $this->getCookieParams();
        if (! isset($cookies[$name])) {
            return $default;
        }

        return is_array($cookies[$name]) ? $cookies[$name][0] : $cookies[$name];
    }

    public function routingResult(): RoutingResult
    {
        $res = $this->getAttribute(RoutingResult::class);
        if (! $res instanceof RoutingResult) {
            return RoutingResult::noMatch();
        }

        return $res;
    }

    /**
     * @note The full url is not urldecoded here.
     */
    public function fullUrlIs(string ...$patterns): bool
    {
        $url = $this->fullUrl();

        foreach ($patterns as $pattern) {
            if (Str::is($url, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function fullUrl(): string
    {
        return $this->getUri()
            ->__toString();
    }

    public function pathIs(string ...$patterns): bool
    {
        $path = $this->decodedPath();

        foreach ($patterns as $pattern) {
            if (Str::is($path, '/' . ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    public function decodedPath(): string
    {
        // We need to make sure that %2F stays %2F
        $path = strtr($this->path(), [
            '%2F' => '%252F',
        ]);

        $segments = explode('/', $path);
        $segments = array_map(fn ($part): string => rawurldecode($part), $segments);

        return implode('/', $segments);
    }

    public function path(): string
    {
        $path = $this->getUri()
            ->getPath();
        if ('' === $path) {
            $path = '/';
        }

        return $path;
    }

    /**
     * A request is considered secure when the scheme is set to "https". If your
     * site runs behind a reverse proxy you have to make sure that your reverse
     * proxy is configured correctly for setting the HTTP_X_FORWARDED_PROTO
     * header. It's not possible to configure trusted proxies because if this is
     * not configured at the server level the entire WP application will
     * misbehave anyway.
     */
    public function isSecure(): bool
    {
        return 'https' === $this->getUri()
            ->getScheme();
    }

    public function isToFrontend(): bool
    {
        return self::TYPE_FRONTEND === $this->type;
    }

    public function isToAdminArea(): bool
    {
        return self::TYPE_ADMIN_AREA === $this->type;
    }

    public function isToApiEndpoint(): bool
    {
        return self::TYPE_API === $this->type;
    }

    public function ip(): ?string
    {
        $ip = $this->server('REMOTE_ADDR');
        if (! is_string($ip)) {
            return null;
        }

        return $ip;
    }

    /**
     * @return mixed
     */
    public function server(string $key, ?string $default = null)
    {
        return Arr::get($this->getServerParams(), $key, $default);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    public function isReadVerb(): bool
    {
        return $this->isMethodSafe();
    }

    public function isMethodSafe(): bool
    {
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE'], true);
    }

    public function isAjax(): bool
    {
        return $this->isXmlHttpRequest();
    }

    public function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' === $this->getHeaderLine('X-Requested-With');
    }

    public function isSendingJson(): bool
    {
        return Str::containsAny($this->getHeaderLine('content-type'), ['/json', '+json']);
    }

    public function isExpectingJson(): bool
    {
        $accepts = $this->getHeaderLine('accept');

        return Str::containsAny($accepts, ['/json', '+json']);
    }

    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    public function accepts(string $content_type): bool
    {
        return $this->matchesType($content_type, $this->getHeaderLine('accept'));
    }

    /**
     * @param string[] $content_types
     */
    public function acceptsOneOf(array $content_types): bool
    {
        $accepts = $this->getHeaderLine('accept');

        foreach ($content_types as $content_type) {
            if ($this->matchesType($content_type, $accepts)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets a value from the parsed body ($_GET). Supports accessing values with
     * 'dot' notation. {@see Arr::get()}.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public function query(?string $key = null, $default = null)
    {
        $query = $this->getQueryParams();

        if (null === $key) {
            return $query;
        }

        return Arr::get($query, $key, $default);
    }

    public function queryString(): string
    {
        $qs = $this->getUri()
            ->getQuery();

        while (Str::endsWith($qs, '&') || Str::endsWith($qs, '=')) {
            $qs = mb_substr($qs, 0, -1);
        }

        return $qs;
    }

    /**
     * Gets a value from the parsed body ($_POST). Supports accessing values
     * with 'dot' notation. {@see Arr::get()}.
     *
     * @param mixed $default
     *
     * @throws RuntimeException if parsed body is not an array
     *
     * @return mixed
     */
    public function post(?string $key = null, $default = null)
    {
        $parsed_body = $this->getParsedBody();

        if (null === $parsed_body) {
            return $default;
        }

        if (! is_array($parsed_body)) {
            throw new RuntimeException(sprintf('%s can not be used if parsed body is not an array.', __METHOD__));
        }

        if (null === $key) {
            return $parsed_body;
        }

        return Arr::get($parsed_body, $key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var(Arr::get($this->inputSource(), $key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function all(): array
    {
        $post = (array) $this->post();
        $query = (array) $this->query();

        if ($this->isReadVerb()) {
            return $query + $post;
        }

        return $post + $query;
    }

    public function realMethod(): string
    {
        $method = Arr::get($this->getServerParams(), 'REQUEST_METHOD', 'GET');
        Assert::stringNotEmpty($method);

        return $method;
    }

    /**
     * @param string|string[] $keys
     */
    public function only($keys): array
    {
        $results = [];

        $input = $this->inputSource();
        $keys = Arr::toArray($keys);

        $placeholder = new stdClass();

        foreach ($keys as $key) {
            /** @var mixed $value */
            $value = Arr::get($input, $key, $placeholder);

            if ($value !== $placeholder) {
                Arr::set($results, $key, $value);
            }
        }

        return $results;
    }

    /**
     * Returns false if any of the keys are either missing or have the value
     * null, '', []. True otherwise.
     *
     * @param string|string[] $keys
     */
    public function filled($keys): bool
    {
        $keys = Arr::toArray($keys);

        foreach ($keys as $key) {
            if ($this->isEmpty($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string|string[] $keys
     */
    public function except($keys): array
    {
        $keys = Arr::toArray($keys);

        $results = $this->inputSource();

        Arr::remove($results, $keys);

        return $results;
    }

    public function has(string $key): bool
    {
        return Arr::has($this->inputSource(), $key);
    }

    /**
     * This method is the opposite of {@see Request::has()} It will return true
     * if none of the keys are in the request and false if at least one key is
     * present.
     */
    public function missing(string $key): bool
    {
        return ! $this->has($key);
    }

    /**
     * @param string[] $keys
     */
    public function hasAny(array $keys): bool
    {
        return Arr::hasAny($this->inputSource(), $keys);
    }

    /**
     * @param string[] $keys
     */
    public function hasAll(array $keys): bool
    {
        return Arr::hasAll($this->inputSource(), $keys);
    }

    public function getHeaderLine($name): string
    {
        return $this->psr_request->getHeaderLine($name);
    }

    public function getUri(): UriInterface
    {
        return $this->psr_request->getUri();
    }

    public function getRequestTarget(): string
    {
        return $this->psr_request->getRequestTarget();
    }

    public function getCookieParams(): array
    {
        return $this->psr_request->getCookieParams();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->psr_request->getAttribute($name, $default);
    }

    public function getServerParams(): array
    {
        return $this->psr_request->getServerParams();
    }

    public function getMethod(): string
    {
        return $this->psr_request->getMethod();
    }

    public function getHeader($name): array
    {
        return $this->psr_request->getHeader($name);
    }

    public function getQueryParams(): array
    {
        return $this->psr_request->getQueryParams();
    }

    public function getParsedBody()
    {
        return $this->psr_request->getParsedBody();
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

    public function withAttribute($name, $value): Request
    {
        return $this->new($this->psr_request->withAttribute($name, $value));
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

    public function userId(): ?int
    {
        /** @var mixed $id */
        $id = $this->getAttribute('snicco.user_id');
        if (! is_int($id) && null !== $id) {
            throw new InvalidArgumentException(
                sprintf("snicco.user_id must be integer or null.\nGot [%s].", gettype($id))
            );
        }

        return $id;
    }

    /**
     * @return static
     */
    public function withUserId(int $user_id): Request
    {
        return $this->withAttribute('snicco.user_id', $user_id);
    }

    public function routeIs(string $route_name): bool
    {
        $route = $this->routingResult()
            ->route();
        if (! $route instanceof Route) {
            return false;
        }

        return $route->getName() === $route_name;
    }

    /**
     * @return static
     */
    private function new(ServerRequestInterface $new_psr_request): Request
    {
        return new self($new_psr_request, $this->type);
    }

    private function isMethod(string $method): bool
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }

    private function matchesType(string $match_against, string $accept_header): bool
    {
        if ('*/*' === $accept_header) {
            return true;
        }

        if ('*' === $accept_header) {
            return true;
        }

        $tok = strtok($match_against, '/');

        if (false === $tok) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Could not parse accept header [%s].', $match_against));
            // @codeCoverageIgnoreEnd
        }

        if ($accept_header === $tok . '/*') {
            return true;
        }

        return $match_against === $accept_header;
    }

    private function inputSource(): array
    {
        $input = in_array($this->realMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            ? $this->getParsedBody()
            : $this->getQueryParams();

        if (! is_array($input)) {
            throw new RuntimeException(sprintf('%s can only be used if the parsed body is an array.', __METHOD__));
        }

        return $input;
    }

    private function isEmpty(string $key): bool
    {
        /** @var mixed $value */
        $value = Arr::get($this->inputSource(), $key);

        if (null === $value) {
            return true;
        }

        if ([] === $value) {
            return true;
        }

        return '' === trim((string) $value);
    }
}
