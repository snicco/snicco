<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional;

use BadMethodCallException;
use LogicException;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Webmozart\Assert\Assert;

use function array_keys;
use function parse_str;
use function strpos;

use const UPLOAD_ERR_OK;

final class Browser extends AbstractBrowser
{
    private HttpKernel $http_kernel;

    private Psr17FactoryDiscovery $psr17_factories;

    private UploadedFileFactoryInterface $file_factory;

    private ServerRequestFactoryInterface $request_factory;

    private StreamFactoryInterface $stream_factory;

    private AdminAreaPrefix $admin_area_prefix;

    private UrlPath $api_prefix;

    /**
     * @param array<string,mixed> $server
     */
    public function __construct(
        HttpKernel $http_kernel,
        Psr17FactoryDiscovery $psr17_factories,
        AdminAreaPrefix $admin_area_prefix,
        UrlPath $api_prefix,
        array $server = [],
        History $history = null,
        CookieJar $cookieJar = null
    ) {
        parent::__construct($server, $history, $cookieJar);
        $this->http_kernel = $http_kernel;
        $this->psr17_factories = $psr17_factories;
        $this->admin_area_prefix = $admin_area_prefix;
        $this->api_prefix = $api_prefix;
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

    protected function filterRequest(\Symfony\Component\BrowserKit\Request $request): Request
    {
        $psr_server_request = $this->request_factory->createServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getServer(),
        );

        $cookies = $request->getCookies();
        Assert::allString($cookies);
        Assert::allString(array_keys($cookies));

        $psr_server_request = $psr_server_request->withCookieParams($cookies);
        $psr_server_request = $psr_server_request->withParsedBody($request->getParameters());

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

        $psr_server_request = $psr_server_request->withUploadedFiles($files);
        $psr_server_request = $psr_server_request->withParsedBody($request->getParameters());

        parse_str($psr_server_request->getUri()->getQuery(), $query);

        if (0 === strpos($psr_server_request->getUri()->getPath(), $this->admin_area_prefix->asString())) {
            $type = Request::TYPE_ADMIN_AREA;
        } elseif (0 === strpos($psr_server_request->getUri()->getPath(), $this->api_prefix->asString())) {
            $type = Request::TYPE_API;
        } else {
            $type = Request::TYPE_FRONTEND;
        }

        return Request::fromPsr($psr_server_request->withQueryParams($query), $type);
    }
}
