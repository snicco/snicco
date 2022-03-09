<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Closure;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Reflector;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Throwable;

use function array_map;
use function call_user_func;
use function is_string;
use function strtolower;

final class MiddlewarePipeline
{
    private HttpErrorHandler $error_handler;
    private MiddlewareFactory $middleware_factory;
    private ContainerInterface $container;

    /**
     * @var array<MiddlewareInterface|MiddlewareBlueprint|class-string<MiddlewareInterface>>
     */
    private array $middleware = [];

    private ?Request $current_request = null;

    /**
     * @var Closure(Request):ResponseInterface
     * @psalm-var Closure(Request=):ResponseInterface $request_handler
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private Closure $request_handler;

    public function __construct(ContainerInterface $container, HttpErrorHandler $error_handler)
    {
        $this->container = $container;
        $this->middleware_factory = new MiddlewareFactory($this->container);
        $this->error_handler = $error_handler;
    }

    public function send(Request $request): MiddlewarePipeline
    {
        $new = clone $this;
        $new->current_request = $request;
        return $new;
    }

    /**
     * @param array<MiddlewareInterface|MiddlewareBlueprint|class-string<MiddlewareInterface>> $middleware
     */
    public function through(array $middleware): MiddlewarePipeline
    {
        foreach ($middleware as $m) {
            if ($m instanceof MiddlewareInterface || $m instanceof MiddlewareBlueprint) {
                continue;
            }
            Reflector::assertInterfaceString($m, MiddlewareInterface::class);
        }

        $new = clone $this;
        $new->middleware = $middleware;
        return $new;
    }

    /**
     * @param Closure(Request):ResponseInterface $request_handler
     * @psalm-param Closure(Request=):ResponseInterface $request_handler
     */
    public function then(Closure $request_handler): Response
    {
        $new = clone $this;
        $new->request_handler = $request_handler;
        return $new->run();
    }

    private function run(): Response
    {
        if (!isset($this->current_request)) {
            throw new LogicException(
                'You cant run a middleware pipeline twice without calling send() first.'
            );
        }

        $stack = $this->lazyNext();

        $response = $stack($this->current_request);

        unset($this->current_request);

        return $response;
    }

    private function lazyNext(): NextMiddleware
    {
        return new NextMiddleware(function (Request $request) {
            try {
                return $this->runNext($request);
            } catch (Throwable $e) {
                return $this->exceptionToHttpResponse($e, $request);
            }
        });
    }

    private function exceptionToHttpResponse(Throwable $e, Request $request): Response
    {
        $psr_response = $this->error_handler->handle($e, $request);

        return $psr_response instanceof Response ? $psr_response : new Response($psr_response);
    }

    private function runNext(Request $request): ResponseInterface
    {
        $middleware = array_shift($this->middleware);

        if (null === $middleware) {
            return call_user_func($this->request_handler, $request);
        }

        $next = $this->lazyNext();

        if ($middleware instanceof MiddlewareBlueprint) {
            $middleware = $this->middleware_factory->create(
                $middleware->class,
                $this->convertStrings($middleware->arguments)
            );
        } elseif (is_string($middleware)) {
            $middleware = $this->middleware_factory->create($middleware);
        }

        if ($middleware instanceof Middleware) {
            $middleware->setContainer($this->container);
        }

        return $middleware->process($request, $next);
    }

    /**
     * @param array<string> $constructor_args
     */
    private function convertStrings(array $constructor_args): array
    {
        return array_map(function ($value) {
            if (strtolower($value) === 'true') {
                return true;
            }
            if (strtolower($value) === 'false') {
                return false;
            }

            if (is_numeric($value)) {
                return intval($value);
            }

            return $value;
        }, $constructor_args);
    }
}
