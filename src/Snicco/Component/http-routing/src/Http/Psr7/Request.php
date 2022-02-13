<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\RoutingResult;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;
use stdClass;
use Webmozart\Assert\Assert;

use function filter_var;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function strtok;
use function strtoupper;
use function substr;
use function trim;

use const FILTER_VALIDATE_BOOLEAN;

final class Request implements ServerRequestInterface
{

    public const TYPE_FRONTEND = 'frontend';
    public const TYPE_ADMIN_AREA = 'admin';
    public const TYPE_API = 'api';

    private ServerRequestInterface $psr_request;

    /**
     * @var "frontend"|"admin"|"api"
     */
    private string $type;

    /**
     * @param self::TYPE_FRONTEND|self::TYPE_ADMIN_AREA|self::TYPE_API $type
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
     * @param self::TYPE_FRONTEND|self::TYPE_ADMIN_AREA|self::TYPE_API $type
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
        $user_agent = substr($this->getHeaderLine('user-agent'), 0, 500);
        if (false === $user_agent || '' === $user_agent) {
            return null;
        }
        return $user_agent;
    }

    /**
     * Returns schema + host + path
     */
    public function url(): string
    {
        $full = (string)$this->getUri();
        return Str::pregReplace('/\?.*/', '', $full);
    }

    /**
     * Returns the value of a received cookie or $default if no cookie with the given name
     * exists.
     *
     * If multiple cookie headers have been sent for $name only the first one will be returned.
     */
    public function cookie(string $name, ?string $default = null): ?string
    {
        /** @var array<string,string|non-empty-list<string>> $cookies */
        $cookies = $this->getCookieParams();
        if (!isset($cookies[$name])) {
            return $default;
        }
        if (is_array($cookies[$name])) {
            $cookie = $cookies[$name][0];
        } else {
            $cookie = $cookies[$name];
        }
        return $cookie;
    }

    public function routingResult(): RoutingResult
    {
        $res = $this->getAttribute(RoutingResult::class);
        if (!$res instanceof RoutingResult) {
            return RoutingResult::noMatch();
        }
        return $res;
    }

    /**
     * @note The full url is not urldecoded here.
     */
    function fullUrlIs(string ...$patterns): bool
    {
        $url = $this->fullUrl();

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function fullUrl(): string
    {
        return $this->getUri()->__toString();
    }

    public function pathIs(string ...$patterns): bool
    {
        $path = $this->decodedPath();

        foreach ($patterns as $pattern) {
            if (Str::is('/' . ltrim($pattern, '/'), $path)) {
                return true;
            }
        }

        return false;
    }

    public function decodedPath(): string
    {
        $path = $this->path();
        return implode(
            '/',
            array_map(function ($part) {
                // Make sure that %2F stays %2F
                return rawurldecode(strtr($part, ['%2F' => '%252F']));
            }, explode('/', $path))
        );
    }

    public function path(): string
    {
        $path = $this->getUri()->getPath();
        if ('' === $path) {
            $path = '/';
        }
        return $path;
    }

    /**
     * A request is considered secure when the scheme is set to "https".
     * If your site runs behind a reverse proxy you have to make sure that your reverse proxy is
     * configured correctly for setting the HTTP_X_FORWARDED_PROTO header. It's not
     * possible to configure trusted proxies because if this is not configured at the server
     * level the entire WP application will misbehave anyway.
     */
    public function isSecure(): bool
    {
        return 'https' === $this->getUri()->getScheme();
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
        if (!is_string($ip)) {
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
        return in_array($this->getMethod(), ['GET', 'HEAD', 'OPTIONS', 'TRACE']);
    }

    public function isAjax(): bool
    {
        return $this->isXmlHttpRequest();
    }

    public function isXmlHttpRequest(): bool
    {
        return 'XMLHttpRequest' == $this->getHeaderLine('X-Requested-With');
    }

    public function isSendingJson(): bool
    {
        return Str::contains($this->getHeaderLine('content-type'), ['/json', '+json']);
    }

    public function isExpectingJson(): bool
    {
        $accepts = $this->getHeaderLine('accept');

        return Str::contains($accepts, ['/json', '+json']);
    }

    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    public function accepts(string $content_type): bool
    {
        return $this->matchesType(
            $content_type,
            $this->getHeaderLine('accept')
        );
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
     * Gets a value from the parsed body ($_GET).
     * Supports 'dot' notation and accessing nested values with * wildcards.
     *
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, $default = null)
    {
        $query = $this->getQueryParams();

        if (null === $key) {
            return $query;
        }
        return Arr::dataGet($query, $key, $default);
    }

    public function queryString(): string
    {
        $qs = $this->getUri()->getQuery();

        while (Str::endsWith($qs, '&') || Str::endsWith($qs, '=')) {
            $qs = mb_substr($qs, 0, -1);
        }

        return $qs;
    }

    /**
     * Gets a value from the parsed body ($_POST).
     * Supports 'dot' notation and accessing nested values with * wildcards.
     *
     * @param mixed $default
     * @return mixed
     *
     * @throws RuntimeException If parsed body is not an array.
     */
    public function post(?string $key = null, $default = null)
    {
        $parsed_body = $this->getParsedBody();

        if (null === $parsed_body) {
            return $default;
        }

        if (!is_array($parsed_body)) {
            throw new RuntimeException(sprintf('%s can not be used if parsed body is not an array.', __METHOD__));
        }

        if (null === $key) {
            return $parsed_body;
        }
        return Arr::dataGet($parsed_body, $key, $default);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return filter_var(Arr::get($this->inputSource(), $key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function all(): array
    {
        $post = (array)$this->post();
        $query = (array)$this->query();

        if ($this->isReadVerb()) {
            return $query + $post;
        }

        return $post + $query;
    }

    public function realMethod(): string
    {
        /** @var string $method */
        $method = Arr::get($this->getServerParams(), 'REQUEST_METHOD', 'GET');
        return $method;
    }

    /**
     * @param string|string[] $keys
     *
     * @psalm-suppress MixedAssignment
     */
    public function only($keys): array
    {
        $results = [];

        $input = $this->inputSource();

        $placeholder = new stdClass;
        $keys = Arr::toArray($keys);

        foreach ($keys as $key) {
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

        Arr::forget($results, $keys);

        return $results;
    }

    /**
     * @param string|non-empty-array<string> $keys
     */
    public function hasAny($keys): bool
    {
        return Arr::hasAny(
            $this->inputSource(),
            $keys
        );
    }

    /**
     * Will return falls if any of the provided keys is missing.
     *
     * @param string|string[] $keys
     *
     */
    public function missing($keys): bool
    {
        return !$this->has($keys);
    }

    /**
     * @param string|string[] $keys
     */
    public function has($keys): bool
    {
        $input = $this->inputSource();

        return Arr::has($input, $keys);
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

    public function withAttribute($name, $value)
    {
        return $this->new($this->psr_request->withAttribute($name, $value));
    }

    public function withProtocolVersion($version)
    {
        return $this->new($this->psr_request->withProtocolVersion($version));
    }

    public function withHeader($name, $value)
    {
        return $this->new($this->psr_request->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return $this->new($this->psr_request->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return $this->new($this->psr_request->withoutHeader($name));
    }

    public function withBody(StreamInterface $body)
    {
        return $this->new($this->psr_request->withBody($body));
    }

    public function withRequestTarget($requestTarget)
    {
        return $this->new($this->psr_request->withRequestTarget($requestTarget));
    }

    public function withMethod($method)
    {
        return $this->new($this->psr_request->withMethod($method));
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->new($this->psr_request->withUri($uri, $preserveHost));
    }

    public function withQueryParams(array $query)
    {
        return $this->new($this->psr_request->withQueryParams($query));
    }

    public function withCookieParams(array $cookies)
    {
        return $this->new($this->psr_request->withCookieParams($cookies));
    }

    public function withoutAttribute($name)
    {
        return $this->new($this->psr_request->withoutAttribute($name));
    }

    public function withParsedBody($data)
    {
        return $this->new($this->psr_request->withParsedBody($data));
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->new($this->psr_request->withUploadedFiles($uploadedFiles));
    }

    /**
     * @return static
     */
    protected function new(ServerRequestInterface $new_psr_request)
    {
        return new self($new_psr_request, $this->type);
    }

    private function isMethod(string $method): bool
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }

    private function matchesType(string $match_against, string $accept_header): bool
    {
        if ($accept_header === '*/*' || $accept_header === '*') {
            return true;
        }

        $tok = strtok($match_against, '/');

        if (false == $tok) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not parse accept header [$match_against].");
            // @codeCoverageIgnoreEnd
        }

        if ($accept_header === $tok . '/*') {
            return true;
        }

        return $match_against === $accept_header;
    }

    private function inputSource(): array
    {
        $input = in_array($this->realMethod(), ['GET', 'HEAD'])
            ? $this->getQueryParams()
            : $this->getParsedBody();

        if (!is_array($input)) {
            throw new RuntimeException(
                sprintf('%s can only be used if the parsed body is an array.', __METHOD__)
            );
        }

        return $input;
    }

    private function isEmpty(string $key): bool
    {
        /** @var mixed $value */
        $value = Arr::get($this->inputSource(), $key, null);

        if (null === $value) {
            return true;
        }
        if ([] === $value) {
            return true;
        }
        if ('' === trim((string)$value)) {
            return true;
        }

        return false;
    }

}