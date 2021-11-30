<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Closure;
use Throwable;
use RuntimeException;
use Snicco\Support\WP;
use Snicco\Support\Arr;
use Whoops\Run as Whoops;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseFactory;
use Snicco\Shared\ContainerAdapter;
use Snicco\Traits\ReflectsCallable;
use Snicco\Contracts\ExceptionHandler;
use Psr\Log\LoggerInterface as Psr3Logger;
use Snicco\Support\ReflectionDependencies;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class ProductionExceptionHandler implements ExceptionHandler
{
    
    use ReflectsCallable;
    
    public const STOP_REPORTING = false;
    
    protected ContainerAdapter $container;
    protected Psr3Logger       $logger;
    protected array            $dont_report = [];
    protected array            $dont_flash  = [];
    private ResponseFactory    $response_factory;
    /** @var Whoops|null */
    private $whoops;
    /**
     * @var Closure[]
     */
    private array $custom_renderers = [];
    
    /**
     * @var Closure[]
     */
    private array $custom_reporters = [];
    
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
    
    public function report(Throwable $e, Request $request, string $psr3_log_level = 'error')
    {
        if (in_array(get_class($e), $this->dont_report)) {
            return;
        }
        
        foreach ($this->custom_reporters as $custom_reporter) {
            $handles_exception = $this->firstClosureParameterType($custom_reporter);
            
            if ( ! $e instanceof $handles_exception) {
                continue;
            }
            
            $deps = (new ReflectionDependencies($this->container))
                ->build($custom_reporter, [$e, $request]);
            
            $result = call_user_func_array($custom_reporter, $deps);
            
            if ($result === self::STOP_REPORTING) {
                return;
            }
        }
        
        if (method_exists($e, 'report')) {
            $deps = (new ReflectionDependencies($this->container))
                ->build([$e, 'report'], [$e, $request]);
            
            if ($e->report(...$deps) === self::STOP_REPORTING) {
                return;
            }
        }
        
        $this->logger->{$psr3_log_level}(
            $e->getMessage(),
            array_merge(
                $this->globalContext($request),
                $this->exceptionContext($e),
                ['exception' => $e]
            )
        );
    }
    
    public function toHttpResponse(Throwable $e, Request $request) :Response
    {
        foreach ($this->custom_renderers as $custom_renderer) {
            $exception_type = $this->firstClosureParameterType($custom_renderer);
            
            if ( ! $e instanceof $exception_type) {
                continue;
            }
            
            return $custom_renderer($e, $request, $this->response_factory);
        }
        
        if (method_exists($e, 'render')) {
            return $this->renderableException($e, $request);
        }
        
        if ( ! $e instanceof HttpException) {
            $e = $this->toHttpException($e, $request);
        }
        
        return $request->isExpectingJson()
            ? $this->renderJson($e)
            : $this->renderHtml($e, $request);
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
    protected function globalContext(Request $request) :array
    {
        try {
            $auth = $request->authenticated();
            
            return array_filter([
                'user_id' => $auth ? WP::userId() : null,
                'user_email' => $auth ? WP::currentUser()->user_email : null,
            ]);
        } catch (Throwable $e) {
            // If we have a fatal error WordPress might not be fully booted yet,
            // and we don't have access to auth functions.
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
    
    private function exceptionContext(Throwable $e)
    {
        if (method_exists($e, 'context')) {
            return $e->context();
        }
        
        return [];
    }
    
    private function renderableException(Throwable $e, Request $request) :Response
    {
        $deps = (new ReflectionDependencies($this->container))->build([$e, 'render'], [$request]);
        
        /** @var Response $response */
        $response = $e->render(...$deps);
        
        if ($response instanceof Response) {
            return $response;
        }
        
        $class = get_class($e);
        $expected = Response::class;
        
        throw new RuntimeException(
            "Return value of $class::render() has to be an instance of [$expected]",
            $e->getCode(),
            $e
        );
    }
    
    private function renderJson(HttpException $e)
    {
        if ($this->isDebug()) {
            return $this->response_factory->json(
                $this->convertExceptionToArray($e),
                $e->httpStatusCode()
            );
        }
        
        return $this->response_factory->json(
            ['message' => $e->getJsonMessage()],
            $e->httpStatusCode()
        );
    }
    
    private function isDebug() :bool
    {
        return $this->whoops instanceof Whoops;
    }
    
    private function convertExceptionToArray(HttpException $e) :array
    {
        return [
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_map(function ($trace) {
                return Arr::except($trace, ['args']);
            }, $e->getTrace()),
        ];
    }
    
    private function renderHtml(HttpException $e, Request $request) :Response
    {
        if ($this->isDebug()) {
            $method = $this->whoops::EXCEPTION_HANDLER;
            
            $status = $e->httpStatusCode();
            $e = $e->getPrevious() instanceof Throwable ? $e->getPrevious() : $e;
            
            return $this->response_factory->html($this->whoops->{$method}($e))
                                          ->withStatus($status);
        }
        
        $content = $this->container[HtmlErrorRenderer::class]->render($e, $request);
        
        return $this->response_factory->html($content)->withStatus($e->httpStatusCode());
    }
    
}