<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Component\HttpRouting\Http\ErrorHandler\Log\RequestAwareLogger;

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
    
    /**
     * @var Displayer[]
     */
    private array $displayers = [];
    
    /**
     * @var Transformer[]
     */
    private array $transformer = [];
    
    /**
     * @param  Displayer[]  $displayers
     * @param  Transformer[]  $transformers
     */
    public function __construct(
        ResponseFactoryInterface $response_factory,
        DisplayerFilter $filter,
        RequestAwareLogger $logger,
        Identifier $identifier,
        array $displayers = [],
        array $transformers = []
    ) {
        $this->response_factory = $response_factory;
        $this->filter = $filter;
        $this->identifier = $identifier;
        $this->logger = $logger;
        
        foreach ($displayers as $displayer) {
            $this->addDisplayer($displayer);
        }
        foreach ($transformers as $transformer) {
            $this->addTransformer($transformer);
        }
    }
    
    public function handle(Throwable $e, RequestInterface $request) :ResponseInterface
    {
        $id = $this->identifier->identify($e);
        
        $this->logger->log($e, $request, $id);
        
        $transformed = $this->transform($e);
        
        $displayer = $this->findBestDisplayer($request, $transformed);
        
        if ( ! $displayer) {
            $response = $this->fallbackResponse($transformed, $id);
        }
        else {
            $response = $this->response_factory->createResponse($transformed->statusCode());
            $response->getBody()->write($displayer->display($transformed, $id));
            $response = $response->withHeader('content-type', $displayer->supportedContentType());
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
    
    private function findBestDisplayer(RequestInterface $request, HttpException $e) :?Displayer
    {
        $displayers = array_values(
            $this->filter->filter($this->displayers, $request, $e)
        );
        
        return $displayers[0] ?? null;
    }
    
    private function addTransformer(Transformer $transformer) :void
    {
        $this->transformer[] = $transformer;
    }
    
    private function transform(Throwable $e) :HttpException
    {
        foreach ($this->transformer as $transformer) {
            $e = $transformer->transform($e);
        }
        
        if ( ! $e instanceof HttpException) {
            $e = new HttpException(
                500,
                'Internal Server Error',
                [],
                $e->getCode(),
                $e
            );
        }
        
        return $e;
    }
    
    private function fallbackResponse(HttpException $transformed, string $id) :ResponseInterface
    {
        $response = $this->response_factory->createResponse($transformed->statusCode());
        
        $code = sprintf(
            "This error can be identified by the code <b>[%s]</b>",
            htmlentities($id, ENT_QUOTES, 'UTF-8')
        );
        
        $response->getBody()->write(
            sprintf(
                '<h1>%s</h1><p>%s</p><p>%s</p><p>%s</p>',
                'Oops! An Error Occurred',
                'Something went wrong on our servers while we were processing your request.',
                $code,
                'Sorry for any inconvenience caused'
            )
        );
        
        return $response->withHeader('content-type', 'text/html; charset=UTF-8');
    }
    
}