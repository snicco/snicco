<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\StrArr\Arr;
use Snicco\Component\StrArr\Str;

use function headers_list;
use function ini_set;
use function ob_get_length;
use function str_replace;
use function strtolower;

/**
 * This class represents Symfony's Response::prepare() method ported to psr7.
 *
 * @see https://github.com/symfony/http-foundation/blob/5.3/ResponseHeaderBag.php
 * @see https://github.com/symfony/http-foundation/blob/5.3/Response.php
 *
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @license https://github.com/symfony/http-foundation/blob/5.3/LICENSE
 */
final class ResponsePreparation
{
    private StreamFactoryInterface $stream_factory;

    private string $charset;

    public function __construct(StreamFactoryInterface $stream_factory, string $charset = 'UTF-8')
    {
        $this->stream_factory = $stream_factory;
        $this->charset = $charset;
    }

    /**
     * @param string[] $sent_headers_with_php A list of headers sent directly with
     *                                        {@see header()}. Use {@see headers_list()}
     */
    public function prepare(Response $response, Request $request, array $sent_headers_with_php): Response
    {
        $response = $this->fixDate($response);
        $response = $this->fixCacheControl($response, $sent_headers_with_php);
        $response = $this->fixContent($response, $request);

        return $this->fixProtocol($response, $request);
    }

    private function fixDate(Response $response): Response
    {
        if (! $response->hasHeader('date')) {
            $response = $response->withHeader('date', gmdate('D, d M Y H:i:s') . ' GMT');
        }

        return $response;
    }

    /**
     * @param string[] $headers_sent_with_php
     */
    private function fixCacheControl(Response $response, array $headers_sent_with_php): Response
    {
        $header = $this->getCacheControlHeader($response, $headers_sent_with_php);

        if ('' === $header) {
            if ($response->hasHeader('Last-Modified')) {
                return $response->withHeader('Cache-Control', 'private, must-revalidate');
            }

            if ($response->hasHeader('Expires')) {
                return $response->withHeader('Cache-Control', 'private, must-revalidate');
            }

            // conservative by default
            return $response->withHeader('Cache-Control', 'no-cache, private');
        }

        if (Str::containsAny($header, ['public', 'private'])) {
            return $response;
        }

        // public if s-maxage is defined, private otherwise
        if (! Str::contains($header, 's-maxage')) {
            return $response->withHeader('Cache-Control', $header . ', private');
        }

        return $response;
    }

    /**
     * @param string[] $headers_sent_with_php
     */
    private function getCacheControlHeader(Response $response, array $headers_sent_with_php): string
    {
        if ($response->hasHeader('cache-control')) {
            return strtolower($response->getHeaderLine('cache-control'));
        }

        $header = Arr::first(
            $headers_sent_with_php,
            fn (string $header): bool => Str::startsWith(strtolower($header), 'cache-control'),
            ''
        );

        return str_replace('cache-control: ', '', strtolower($header));
    }

    private function fixContent(Response $response, Request $request): Response
    {
        if ($response->isInformational() || $response->isEmpty()) {
            // prevent PHP from sending the Content-Type header based on default_mimetype
            ini_set('default_mimetype', '');

            return $response->withBody($this->getEmptyStream())
                ->withoutHeader('content-type')
                ->withoutHeader('content-length');
        }

        // Fix content type
        $content_type = $response->getHeaderLine('content-type');

        if ('' === $content_type) {
            $response = $response->withContentType(sprintf('text/html; charset=%s', $this->charset));
        } elseif (Str::startsWith($content_type, 'text/')
            && ! Str::contains($content_type, 'charset')) {
            $content_type = trim($content_type, ';');
            $response = $response->withContentType(sprintf('%s; charset=%s', $content_type, $this->charset));
        }

        // Fix Content-Length, don't add if anything is buffered since we will mess up plugins that use it.
        if (! $response->hasHeader('content-length')
            && ! $response->hasEmptyBody()
            && ! ob_get_length()
        ) {
            $size = (string) ($response->getBody()->getSize());
            $response = $response->withHeader('content-length', $size);
        }

        // Remove content-length if transfer-encoding
        if ($response->hasHeader('transfer-encoding')) {
            $response = $response->withoutHeader('content-length');
        }

        // Fix HEAD method if body: RFC2616 14.13
        if ($request->isHead()) {
            $response = $response->withBody($this->getEmptyStream());
        }

        return $response;
    }

    private function getEmptyStream(): StreamInterface
    {
        return $this->stream_factory->createStream('');
    }

    private function fixProtocol(Response $response, Request $request): Response
    {
        if ('HTTP/1.0' !== $request->server('SERVER_PROTOCOL')) {
            $response = $response->withProtocolVersion('1.1');
        }

        return $response;
    }
}
