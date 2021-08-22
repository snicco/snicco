<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Closure;
use Throwable;
use RuntimeException;
use Snicco\Support\WP;
use Whoops\Run as Whoops;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\ExceptionHandler;
use Psr\Log\LoggerInterface as Psr3Logger;
use Illuminate\Support\Traits\ReflectsClosures;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class ProductionExceptionHandler implements ExceptionHandler
{
    
    use ReflectsClosures;
    
    protected ContainerAdapter $container;
    
    protected Psr3Logger $logger;
    
    protected array $dont_report = [];
    
    protected array $dont_flash = [];
    
    /**
     * @var Closure[]
     */
    private array $custom_renderers = [];
    
    /**
     * @var Closure[]
     */
    private array $custom_reporters = [];
    
    private ResponseFactory $response_factory;
    
    /** @var Whoops|null */
    private $whoops;
    
    /**
     * @param  ContainerAdapter  $container
     * @param  Psr3Logger  $logger
     * @param  ResponseFactory  $response_factory
     * @param  null|Whoops  $whoops
     */
    public function __construct(ContainerAdapter $container, Psr3Logger $logger, ResponseFactory $response_factory, $whoops = null)
    {
        
        $this->container = $container;
        $this->logger = $logger;
        $this->response_factory = $response_factory;
        $this->whoops = $whoops;
        $this->registerCallbacks();
        
    }
    
    public function report(Throwable $e, Request $request)
    {
        $this->logException($e, $request);
    }
    
    public function toHttpResponse(Throwable $e, Request $request) :Response
    {
        return $this->convertToResponse($e, $request);
    }
    
    public function renderable(callable $render_using) :ProductionExceptionHandler
    {
        if ( ! $render_using instanceof Closure) {
            $render_using = Closure::fromCallable($render_using);
        }
        
        $this->custom_renderers[] = $render_using;
        
        return $this;
        
    }
    
    public function reportable(callable $report_using) :ProductionExceptionHandler
    {
        if ( ! $report_using instanceof Closure) {
            $report_using = Closure::fromCallable($report_using);
        }
        
        $this->custom_reporters[] = $report_using;
        
        return $this;
        
    }
    
    protected function registerCallbacks()
    {
        //
    }
    
    /**
     * Override this method from a child class to create global context
     * that should be added to every log entry.
     */
    protected function globalContext() :array
    {
        
        try {
            return array_filter([
                'user_id' => WP::userId(),
            ]);
        } catch (Throwable $e) {
            return [];
        }
        
    }
    
    /**
     * Override this method from a child class to create
     * your own default response for fatal errors that can not be transformed by this error
     * handler.
     *
     * @param  Throwable  $e
     * @param  Request  $request
     *
     * @return HttpException
     */
    protected function toHttpException(Throwable $e, Request $request) :HttpException
    {
        return new HttpException(500, $e->getMessage(), $e);
    }
    
    private function logException(Throwable $exception, Request $request)
    {
        
        if (in_array(get_class($exception), $this->dont_report)) {
            return;
        }
        
        foreach ($this->custom_reporters as $custom_reporter) {
            
            $handles_exception = $this->firstClosureParameterType($custom_reporter);
            
            if ( ! $exception instanceof $handles_exception) {
                continue;
            }
            
            $keep_reporting = $this->container->call(
                $custom_reporter,
                ['request' => $request, 'exception' => $exception, 'e' => $exception]
            );
            
            if ($keep_reporting === false) {
                return;
            }
            
        }
        
        if (method_exists($exception, 'report')) {
            
            if ($this->container->call([$exception, 'report']) === false) {
                
                return;
                
            }
            
        }
        
        $this->logger->error(
            $exception->getMessage(),
            array_merge(
                $this->globalContext(),
                $this->exceptionContext($exception),
                ['exception' => $exception]
            )
        );
        
    }
    
    private function exceptionContext(Throwable $e)
    {
        
        if (method_exists($e, 'context')) {
            return $e->context();
        }
        
        return [];
    }
    
    private function convertToResponse(Throwable $e, Request $request) :Response
    {
        
        foreach ($this->custom_renderers as $custom_renderer) {
            
            $exception_type = $this->firstClosureParameterType($custom_renderer);
            
            if ( ! $e instanceof $exception_type) {
                continue;
            }
            
            $response = $custom_renderer($e, $request, $this->response_factory);
            
            if ($response instanceof Response) {
                return $response;
            }
            
        }
        
        if (method_exists($e, 'render')) {
            
            return $this->renderableException($e, $request);
            
        }
        
        if ( ! $e instanceof HttpException) {
            
            $e = $this->toHttpException($e, $request);
            
        }
        
        return $this->renderHttpException($e, $request);
        
    }
    
    private function renderableException(Throwable $e, Request $request) :Response
    {
        
        /** @var Response $response */
        $response = $this->container->call([$e, 'render'], ['request' => $request]);
        
        try {
            return $this->response_factory->toResponse($response);
        } catch (\HttpException $response_exception) {
            
            $class = get_class($e);
            throw new RuntimeException(
                "Return value of $class::render() could not be transformed to a Response object",
                0,
                $e
            );
            
        }
        
    }
    
    private function renderHttpException(HttpException $http_exception, Request $request) :Response
    {
        
        if ($request->isExpectingJson()) {
            
            return $this->response_factory->json(
                ['message' => $http_exception->getJsonMessage()],
                $http_exception->httpStatusCode()
            );
            
        }
        
        return $this->response_factory->error($http_exception, $request);
        
    }
    
}