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

use function sprintf;
use function strtolower;
use function array_values;
use function htmlentities;

use const ENT_QUOTES;

final class HttpErrorHandler implements HttpErrorHandlerInterface
{
    
    private ResponseFactoryInterface $response_factory;
    private DisplayerFilter          $filter;
    private RequestAwareLogger       $logger;
    private Identifier               $identifier;
    private InformationProvider      $information_provider;
    
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
        Identifier $identifier,
        InformationProvider $information_provider,
        array $displayers = []
    ) {
        $this->response_factory = $response_factory;
        $this->filter = $filter;
        $this->identifier = $identifier;
        $this->information_provider = $information_provider;
        $this->logger = $logger;
        
        foreach ($displayers as $displayer) {
            $this->addDisplayer($displayer);
        }
    }
    
    public function handle(Throwable $transformed, RequestInterface $request) :ResponseInterface
    {
        $info = $this->information_provider->provideFor(
            new IdentifiedThrowable(
                $transformed,
                $this->identifier->identify($transformed)
            )
        );
        
        $this->logger->log($info, $request);
        
        $displayer = $this->findBestDisplayer($request, $info);
        
        if ( ! $displayer) {
            $response = $this->fallbackResponse($info);
        }
        else {
            $response = $this->response_factory->createResponse($info->statusCode());
            $response->getBody()->write($displayer->display($info));
            $response = $response->withHeader('content-type', $displayer->supportedContentType());
        }
        
        $transformed = $info->transformed();
        
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
    
    private function addDisplayer(Displayer $displayer) :void
    {
        $this->displayers[] = $displayer;
    }
    
    private function findBestDisplayer(RequestInterface $request, ExceptionInformation $info) :?Displayer
    {
        $displayers = array_values(
            $this->filter->filter($this->displayers, $request, $info)
        );
        
        return $displayers[0] ?? null;
    }
    
    private function fallbackResponse(ExceptionInformation $info) :ResponseInterface
    {
        $response = $this->response_factory->createResponse($info->statusCode());
        
        $code = sprintf(
            "This error can be identified by the code <b>[%s]</b>",
            htmlentities($info->identifier(), ENT_QUOTES, 'UTF-8')
        );
        
        $response->getBody()->write(
            sprintf(
                '<h1>%s</h1><p>%s</p><p>%s</p><p>%s</p>',
                'Oops! An Error Occurred',
                'Something went wrong on our servers while we were processing your request.',
                $code,
                'Sorry for any inconvenience caused.'
            )
        );
        
        return $response->withHeader('content-type', 'text/html; charset=UTF-8');
    }
    
}