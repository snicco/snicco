<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\Psr7;

use BadMethodCallException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Snicco\Component\HttpRouting\Http\Cookie;
use Snicco\Component\HttpRouting\Http\Cookies;

use function in_array;
use function sprintf;

class Response implements ResponseInterface
{
    private ResponseInterface $psr7_response;

    private Cookies $cookies;

    /**
     * @var array<string,string>
     */
    private array $flash_messages = [];

    /**
     * @var array<string,string>
     */
    private array $old_input = [];

    /**
     * @var array<string,array<string,string[]>>
     */
    private array $errors = [];

    public function __construct(ResponseInterface $psr7_response)
    {
        $this->psr7_response = $psr7_response;
        $this->cookies = ($psr7_response instanceof Response)
            ? $psr7_response->cookies()
            : new Cookies();
    }

    /**
     * @param mixed $value
     *
     * @return never
     */
    final public function __set(string $name, $value)
    {
        throw new BadMethodCallException(
            sprintf(sprintf('Cannot set undefined property [%s] on immutable class [%%s]', $name), static::class)
        );
    }

    final public function withAddedHeader($name, $value): self
    {
        return $this->new($this->psr7_response->withAddedHeader($name, $value));
    }

    final public function withHeader($name, $value): self
    {
        return $this->new($this->psr7_response->withHeader($name, $value));
    }

    final public function withBody(StreamInterface $body): self
    {
        return $this->new($this->psr7_response->withBody($body));
    }

    final public function getStatusCode(): int
    {
        return $this->psr7_response->getStatusCode();
    }

    final public function getHeader($name): array
    {
        return $this->psr7_response->getHeader($name);
    }

    final public function getBody(): StreamInterface
    {
        return $this->psr7_response->getBody();
    }

    final public function withProtocolVersion($version): self
    {
        return $this->new($this->psr7_response->withProtocolVersion($version));
    }

    final public function withoutHeader($name): self
    {
        return $this->new($this->psr7_response->withoutHeader($name));
    }

    final public function withStatus($code, $reasonPhrase = ''): self
    {
        return $this->new($this->psr7_response->withStatus($code, $reasonPhrase));
    }

    final public function getProtocolVersion(): string
    {
        return $this->psr7_response->getProtocolVersion();
    }

    /**
     * @return array<string, string[]>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    final public function getHeaders(): array
    {
        return $this->psr7_response->getHeaders();
    }

    final public function hasHeader($name): bool
    {
        return $this->psr7_response->hasHeader($name);
    }

    final public function getHeaderLine($name): string
    {
        return $this->psr7_response->getHeaderLine($name);
    }

    final public function getReasonPhrase(): string
    {
        return $this->psr7_response->getReasonPhrase();
    }

    final public function cookies(): Cookies
    {
        return $this->cookies;
    }

    /**
     * @return static
     */
    final public function withNoIndex(?string $bot = null): self
    {
        $value = $bot ? $bot . ': noindex' : 'noindex';

        return $this->withAddedHeader('X-Robots-Tag', $value);
    }

    /**
     * @return static
     */
    final public function withNoFollow(?string $bot = null): self
    {
        $value = $bot ? $bot . ': nofollow' : 'nofollow';

        return $this->withAddedHeader('X-Robots-Tag', $value);
    }

    /**
     * @return static
     */
    final public function withNoRobots(?string $bot = null): self
    {
        $value = $bot ? $bot . ': none' : 'none';

        return $this->withAddedHeader('X-Robots-Tag', $value);
    }

    /**
     * @return static
     */
    final public function withNoArchive(?string $bot = null): self
    {
        $value = $bot ? $bot . ': noarchive' : 'noarchive';

        return $this->withAddedHeader('X-Robots-Tag', $value);
    }

    /**
     * @return static
     */
    final public function withContentType(string $content_type): self
    {
        return $this->withHeader('content-type', $content_type);
    }

    /**
     * @return static
     */
    final public function withCookie(Cookie $cookie): self
    {
        $response = clone $this;
        $response->cookies = $this->cookies->withCookie($cookie);

        return $response;
    }

    /**
     * @return static
     */
    final public function withoutCookie(string $name, string $path = '/'): self
    {
        $cookie = (new Cookie($name, 'deleted'))
            ->withExpiryTimestamp(1)
            ->withPath($path);

        $response = clone $this;
        $response->cookies = $this->cookies->withCookie($cookie);

        return $response;
    }

    /**
     * @param array<string,string> $flash
     *
     * @return static
     */
    final public function withFlashMessages(array $flash): self
    {
        $flash_messages = $this->flash_messages;
        foreach ($flash as $k => $v) {
            $flash_messages[$k] = $v;
        }

        $response = clone $this;

        $response->flash_messages = $flash_messages;

        return $response;
    }

    /**
     * @param array<string,string> $old_input
     *
     * @return static
     */
    final public function withOldInput(array $old_input): self
    {
        $_input = $this->old_input;
        foreach ($old_input as $k => $v) {
            $_input[$k] = $v;
        }

        $response = clone $this;

        $response->old_input = $_input;

        return $response;
    }

    /**
     * @param array<string,string|string[]> $errors
     */
    final public function withErrors(array $errors, string $namespace = 'default'): self
    {
        $_errors = $this->errors;
        foreach ($errors as $key => $messages) {
            $messages = (array) $messages;
            foreach ($messages as $message) {
                $_errors[$namespace][$key][] = $message;
            }
        }

        $response = clone $this;

        $response->errors = $_errors;

        return $response;
    }

    final public function withHtml(StreamInterface $html): self
    {
        return $this->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($html);
    }

    /**
     * @return static
     */
    final public function withJson(StreamInterface $json): self
    {
        return $this->withHeader('Content-Type', 'application/json')
            ->withBody($json);
    }

    final public function isRedirect(string $location = null): bool
    {
        return in_array($this->getStatusCode(), [201, 301, 302, 303, 307, 308], true)
            && (null === $location || $location === $this->getHeaderLine('Location'));
    }

    final public function isSuccessful(): bool
    {
        if ($this->getStatusCode() < 200) {
            return false;
        }

        return $this->getStatusCode() < 300;
    }

    final public function isOk(): bool
    {
        return 200 === $this->getStatusCode();
    }

    final public function isNotFound(): bool
    {
        return 404 === $this->getStatusCode();
    }

    final public function isForbidden(): bool
    {
        return 403 === $this->getStatusCode();
    }

    final public function isInformational(): bool
    {
        if ($this->getStatusCode() < 100) {
            return false;
        }

        return $this->getStatusCode() < 200;
    }

    final public function isRedirection(): bool
    {
        $status = $this->getStatusCode();

        return $status >= 300 && $status < 400;
    }

    final public function isClientError(): bool
    {
        $status = $this->getStatusCode();

        return $status >= 400 && $status < 500;
    }

    final public function isServerError(): bool
    {
        $status = $this->getStatusCode();

        return $status >= 500 && $status < 600;
    }

    final public function isEmpty(): bool
    {
        return in_array($this->getStatusCode(), [204, 205, 304], true);
    }

    final public function hasEmptyBody(): bool
    {
        return ((int) ($this->getBody()->getSize())) === 0;
    }

    /**
     * @return array<string,string>
     */
    final public function flashMessages(): array
    {
        return $this->flash_messages;
    }

    /**
     * @return array<string,string>
     */
    final public function oldInput(): array
    {
        return $this->old_input;
    }

    /**
     * @return array<string,array<string,string[]>>
     */
    final public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return static
     */
    private function new(ResponseInterface $new_psr_response): self
    {
        $new = clone $this;
        $new->psr7_response = $new_psr_response;

        return $new;
    }
}
