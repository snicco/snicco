<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\Psr7ErrorHandler\Displayer\ExceptionDisplayer;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackHtmlDisplayer;
use Snicco\Component\Psr7ErrorHandler\Displayer\FallbackJsonDisplayer;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\ContentType;
use Snicco\Component\Psr7ErrorHandler\DisplayerFilter\DisplayerFilter;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformationProvider;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Throwable;

use function array_values;
use function strtolower;

final class ProductionErrorHandler implements HttpErrorHandler
{
    private ResponseFactoryInterface $response_factory;
    private DisplayerFilter $filter;
    private RequestAwareLogger $logger;
    private ExceptionInformationProvider $information_provider;

    /**
     * @var ExceptionDisplayer[]
     */
    private array $displayers;

    /**
     * @param ExceptionDisplayer[] $displayers
     */
    public function __construct(
        ResponseFactoryInterface $response_factory,
        RequestAwareLogger $logger,
        ExceptionInformationProvider $information_provider,
        DisplayerFilter $filter,
        ExceptionDisplayer ...$displayers
    ) {
        $this->response_factory = $response_factory;
        $this->filter = $filter;
        $this->information_provider = $information_provider;
        $this->logger = $logger;
        $this->displayers = $displayers;
    }

    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $info = $this->information_provider->createFor($e, $request);

        try {
            $this->logException($info);
        } catch (Throwable $logging_error) {
            $this->logException($this->information_provider->createFor($logging_error, $request));
        }

        try {
            $response = $this->createResponse(
                $info,
                $this->findBestDisplayer($request, $info)
            );
        } catch (Throwable $display_error) {
            return $this->handleDisplayError($display_error, $request);
        }

        return $this->withHttpHeaders($info->transformedException(), $response);
    }

    private function logException(ExceptionInformation $info): void
    {
        $this->logger->log($info);
    }

    private function createResponse(ExceptionInformation $info, ExceptionDisplayer $displayer): ResponseInterface
    {
        $response = $this->response_factory->createResponse(
            $info->statusCode()
        );

        $response->getBody()->write(
            $displayer->display($info)
        );

        return $response->withHeader('content-type', $displayer->supportedContentType());
    }

    private function findBestDisplayer(ServerRequestInterface $request, ExceptionInformation $info): ExceptionDisplayer
    {
        $displayers = array_values(
            $this->filter->filter($this->displayers, $request, $info)
        );

        if (isset($displayers[0])) {
            return $displayers[0];
        }
        $content_type_filter = new ContentType();

        $displayers = array_values(
            $content_type_filter->filter(
                [$html = new FallbackHtmlDisplayer(), new FallbackJsonDisplayer()],
                $request,
                $info
            )
        );

        if (isset($displayers[0])) {
            return $displayers[0];
        }

        return $html;
    }

    private function handleDisplayError(Throwable $display_error, ServerRequestInterface $request): ResponseInterface
    {
        $info = $this->information_provider->createFor($display_error, $request);
        $this->logException($info);
        $res = $this->response_factory->createResponse(500);
        $res->getBody()->write('Internal Server Error');
        return $res->withHeader('content-type', 'text/plain');
    }

    private function withHttpHeaders(Throwable $transformed, ResponseInterface $response): ResponseInterface
    {
        if (!$transformed instanceof HttpException) {
            return $response;
        }

        foreach ($transformed->headers() as $name => $value) {
            if ('content-type' !== strtolower($name)) {
                $response = $response->withHeader($name, $value);
            }
        }
        return $response;
    }
}
