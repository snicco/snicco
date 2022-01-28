<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Component\Psr7ErrorHandler\Log\RequestAwareLogger;
use Snicco\Component\Psr7ErrorHandler\Information\InformationProvider;
use Snicco\Component\Psr7ErrorHandler\Information\ExceptionInformation;

use function strtolower;
use function array_values;

/**
 * @api
 */
final class HttpErrorHandler implements HttpErrorHandlerInterface
{
    
    private ResponseFactoryInterface $response_factory;
    private DisplayerFilter          $filter;
    private RequestAwareLogger       $logger;
    private InformationProvider      $information_provider;
    private Displayer                $fallback_displayer;
    
    /**
     * @var Displayer[]
     */
    private array $displayers = [];
    
    /**
     * @param  Displayer[]  $displayers
     */
    public function __construct(
        ResponseFactoryInterface $response_factory,
        DisplayerFilter $filter,
        RequestAwareLogger $logger,
        InformationProvider $information_provider,
        Displayer $default_displayer,
        array $displayers = []
    ) {
        $this->response_factory = $response_factory;
        $this->filter = $filter;
        $this->information_provider = $information_provider;
        $this->logger = $logger;
        
        foreach ($displayers as $displayer) {
            $this->addDisplayer($displayer);
        }
        $this->fallback_displayer = $default_displayer;
    }
    
    public function handle(Throwable $e, RequestInterface $request) :ResponseInterface
    {
        $info = $this->information_provider->provideFor($e);
        
        $this->logException($info, $request);
        
        $response = $this->createResponse(
            $info,
            $this->findBestDisplayer($request, $info)
        );
        
        return $this->withHttpHeaders($info->transformedException(), $response);
    }
    
    private function addDisplayer(Displayer $displayer) :void
    {
        $this->displayers[] = $displayer;
    }
    
    private function findBestDisplayer(RequestInterface $request, ExceptionInformation $info) :Displayer
    {
        $displayers = array_values(
            $this->filter->filter($this->displayers, $request, $info)
        );
        
        return $displayers[0] ?? $this->fallback_displayer;
    }
    
    private function logException(ExceptionInformation $info, RequestInterface $request) :void
    {
        $this->logger->log($info, $request);
    }
    
    private function createResponse(ExceptionInformation $info, Displayer $displayer) :ResponseInterface
    {
        $response = $this->response_factory->createResponse(
            $info->statusCode()
        );
        
        $response->getBody()->write(
            $displayer->display($info)
        );
        
        return $response->withHeader('content-type', $displayer->supportedContentType());
    }
    
    private function withHttpHeaders($transformed, $response) :ResponseInterface
    {
        if ( ! $transformed instanceof HttpException) {
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