<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional;

use BadMethodCallException;
use LogicException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as Psr7Request;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Snicco\Bundle\HttpRouting\ApiRequestDetector;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\StrArr\Str;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\Request as BrowserKitRequest;
use Webmozart\Assert\Assert;

use function array_keys;
use function array_merge;
use function in_array;
use function is_array;
use function parse_str;
use function strtolower;

use const UPLOAD_ERR_OK;

final class Browser extends AbstractBrowser
{
    private HttpKernel $http_kernel;

    private Psr17FactoryDiscovery $psr17_factories;

    private UploadedFileFactoryInterface $file_factory;

    private ServerRequestFactoryInterface $request_factory;

    private StreamFactoryInterface $stream_factory;

    private AdminAreaPrefix $admin_area_prefix;

    private ApiRequestDetector $api_request_detector;

    /**
     * @param array<string,mixed> $server
     *
     * @psalm-internal Snicco\Bundle\Testing
     */
    public function __construct(
        HttpKernel $http_kernel,
        Psr17FactoryDiscovery $psr17_factories,
        AdminAreaPrefix $admin_area_prefix,
        ApiRequestDetector $api_request_detector,
        array $server = [],
        History $history = null,
        CookieJar $cookieJar = null
    ) {
        parent::__construct($server, $history, $cookieJar);
        $this->http_kernel = $http_kernel;
        $this->psr17_factories = $psr17_factories;
        $this->admin_area_prefix = $admin_area_prefix;
        $this->api_request_detector = $api_request_detector;
        $this->request_factory = $this->psr17_factories->createServerRequestFactory();
        $this->file_factory = $this->psr17_factories->createUploadedFileFactory();
        $this->stream_factory = $this->psr17_factories->createStreamFactory();
    }

    public function lastResponse(): AssertableResponse
    {
        return $this->getResponse();
    }

    public function lastDOM(): AssertableDOM
    {
        if ($this->lastResponse()->getPsrResponse() instanceof DelegatedResponse) {
            throw new LogicException('The response was delegated to WordPress so that no DOM is available.');
        }

        return new AssertableDOM($this->getCrawler());
    }

    /**
     * @return never
     */
    public function getRequest(): void
    {
        throw new BadMethodCallException(__METHOD__ . ' is not implemented since psr7 requests are immutable.');
    }

    public function getResponse(): AssertableResponse
    {
        $response = parent::getResponse();
        Assert::isInstanceOf($response, Response::class);

        /** @var Response $response */

        return new AssertableResponse($response);
    }

    protected function doRequest(object $request): Response
    {
        Assert::isInstanceOf($request, Request::class);

        /** @var Request $request */

        return $this->http_kernel->handle($request);
    }

    protected function filterResponse(object $response): \Symfony\Component\BrowserKit\Response
    {
        Assert::isInstanceOf($response, Response::class);

        /** @var Response $response */

        return new \Symfony\Component\BrowserKit\Response(
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }

    protected function filterRequest(BrowserKitRequest $request): Request
    {
        $psr_server_request = $this->request_factory->createServerRequest(
            $request->getMethod(),
            $request->getUri(),
            array_merge([
                'REQUEST_METHOD' => $request->getMethod(),
            ], $request->getServer())
        );

        $psr_server_request = $this->addHeadersFromServer($request, $psr_server_request);
        $psr_server_request = $this->addCookies($request, $psr_server_request);
        $psr_server_request = $this->addRequestBody($request, $psr_server_request);
        $psr_server_request = $this->addFiles($request, $psr_server_request);

        parse_str($psr_server_request->getUri()->getQuery(), $query);

        $path = $psr_server_request->getUri()
            ->getPath();

        if (Str::startsWith($path, $this->admin_area_prefix->asString())) {
            $type = Request::TYPE_ADMIN_AREA;
        } elseif ($this->api_request_detector->isAPIRequest($path)) {
            $type = Request::TYPE_API;
        } else {
            $type = Request::TYPE_FRONTEND;
        }

        return Request::fromPsr($psr_server_request->withQueryParams($query), $type);
    }

    private function addHeadersFromServer(BrowserKitRequest $browser_kit_request, Psr7Request $request): Psr7Request
    {
        foreach ($browser_kit_request->getServer() as $key => $value) {
            Assert::stringNotEmpty($key);

            $http_header = Str::startsWith($key, 'HTTP_');
            $content_header = Str::startsWith($key, 'CONTENT_');

            if (! $http_header && ! $content_header) {
                continue;
            }

            if (is_array($value)) {
                Assert::allStringNotEmpty($value);
            } else {
                Assert::stringNotEmpty($value);
            }

            if (Str::startsWith($key, 'HTTP_')) {
                $header_name = Str::afterFirst($key, 'HTTP_');

                $header_name = strtolower(Str::replaceAll($header_name, '_', '-'));

                // These are already added by symfony.
                if (in_array($header_name, ['host', 'referer'], true)) {
                    $request = $request->withHeader($header_name, $value);
                } else {
                    $request = $request->withAddedHeader($header_name, $value);
                }

                continue;
            }

            if (Str::startsWith($key, 'CONTENT_')) {
                $header_name = 'content-' . strtolower(Str::afterFirst($key, 'CONTENT_'));
                $request = $request->withAddedHeader($header_name, $value);
            }
        }

        return $request;
    }

    private function addCookies(BrowserKitRequest $request, Psr7Request $psr_server_request): Psr7Request
    {
        $cookies = $request->getCookies();
        Assert::allString($cookies);
        Assert::allString(array_keys($cookies));

        return $psr_server_request->withCookieParams($cookies);
    }

    private function addRequestBody(BrowserKitRequest $request, Psr7Request $psr_server_request): Psr7Request
    {
        $params = $request->getParameters();
        $raw_body = $request->getContent();

        if ($params && $raw_body) {
            throw new LogicException('Its not possible to pass a raw request body and an array of parameters.');
        }
        if ([] !== $params) {
            $psr_server_request = $psr_server_request->withParsedBody($request->getParameters());
        } elseif (null !== $raw_body) {
            $psr_server_request = $psr_server_request->withBody(
                $this->stream_factory->createStream($raw_body)
            );
        }

        return $psr_server_request;
    }

    private function addFiles(BrowserKitRequest $request, Psr7Request $psr_server_request): Psr7Request
    {
        $files = [];

        foreach ($request->getFiles() as $name => $path) {
            Assert::stringNotEmpty($name);
            Assert::stringNotEmpty($path);
            $files[] = $this->file_factory->createUploadedFile(
                $stream = $this->stream_factory->createStreamFromFile($path),
                $stream->getSize(),
                UPLOAD_ERR_OK,
                $name
            );
        }

        return $psr_server_request->withUploadedFiles($files);
    }
}
