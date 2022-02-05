<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Closure;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Snicco\Component\StrArr\Arr;
use Throwable;
use Webmozart\Assert\Assert;

use function array_map;
use function gettype;
use function is_string;
use function strtolower;

/**
 * @interal
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MiddlewarePipeline
{

    private HttpErrorHandlerInterface $error_handler;
    private MiddlewareFactory $middleware_factory;
    private ContainerInterface $container;

    /**
     * @var array<MiddlewareInterface|MiddlewareBlueprint>
     */
    private array $middleware = [];
    
    private Request $current_request;

    /**
     * @var Closure(Request):ResponseInterface $request_handler
     * @psalm-var Closure(Request=):ResponseInterface $request_handler
     */
    private Closure $request_handler;

    private bool $exhausted = false;

    public function __construct(ContainerInterface $container, HttpErrorHandlerInterface $error_handler)
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
     * @param MiddlewareInterface|MiddlewareInterface[]|MiddlewareBlueprint|MiddlewareBlueprint[] $middleware
     */
    public function through($middleware): MiddlewarePipeline
    {
        $new = clone $this;
        $middleware = Arr::toArray($middleware);

        foreach ($middleware as $m) {
            if ($m instanceof MiddlewareInterface) {
                continue;
            }
            Assert::isInstanceOf($m, MiddlewareBlueprint::class);
        }
        $new->middleware = $middleware;
        return $new;
    }

    /**
     * @param Closure(Request):ResponseInterface $request_handler
     * @psalm-param Closure(Request=):ResponseInterface $request_handler
     */
    public function then(Closure $request_handler): Response
    {
        $this->request_handler = $request_handler;
        return $this->run();
    }

    private function run(): Response
    {
        if (!isset($this->current_request)) {
            throw new LogicException(
                'You cant run a middleware pipeline twice without calling send() first.'
            );
        }

        $stack = $this->buildMiddlewareStack();

        $response = $stack($this->current_request);
        unset($this->current_request);
        return $response;
    }

    private function buildMiddlewareStack(): NextMiddleware
    {
        return $this->nextMiddleware();
    }

    private function nextMiddleware(): NextMiddleware
    {
        if ($this->exhausted) {
            throw new LogicException('The middleware pipeline is exhausted.');
        }

        if ($this->middleware === []) {
            $this->exhausted = true;

            return new NextMiddleware(function (ServerRequestInterface $request): ResponseInterface {
                try {
                    return call_user_func($this->request_handler, $request);
                } catch (Throwable $e) {
                    return $this->exceptionToHttpResponse($e, $request);
                }
            });
        }

        return new NextMiddleware(function (ServerRequestInterface $request) {
            try {
                return $this->runNextMiddleware($request);
            } catch (Throwable $e) {
                return $this->exceptionToHttpResponse($e, $request);
            }
        });
    }

    private function exceptionToHttpResponse(Throwable $e, ServerRequestInterface $request): Response
    {
        $psr_7_response = $this->error_handler->handle($e, $request);
        return $psr_7_response instanceof Response
            ? $psr_7_response
            : new Response($psr_7_response);
    }

    private function runNextMiddleware(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = array_shift($this->middleware);

        if ($middleware instanceof MiddlewareInterface) {
            if ($middleware instanceof AbstractMiddleware) {
                $middleware->setContainer($this->container);
            }
            $instance = $middleware;
        } elseif ($middleware instanceof MiddlewareBlueprint) {
            $instance = $this->middleware_factory->create(
                $middleware->class(),
                $this->convertStrings($middleware->arguments())
            );
        } else {
            throw new InvalidArgumentException(
                '$middleware must be of type MiddlewareInterface or MiddlewareBlueprint. Got: '
                . gettype($middleware)
            );
        }

        return $instance->process($request, $this->nextMiddleware());
    }

    private function convertStrings(array $constructor_args): array
    {
        return array_map(function ($value) {
            if (!is_string($value)) {
                return $value;
            }

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