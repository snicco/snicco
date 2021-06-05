<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Closure;
    use Contracts\ContainerAdapter;
    use LogicException;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Support\Arr;
    use ReflectionPayload\ReflectionPayload;

    use function collect;

    class Pipeline
    {

        /**
         * @var ErrorHandlerInterface
         */
        private $error_handler;

        /**
         * The container implementation.
         *
         * @var ContainerAdapter
         */
        private $container;

        /**
         *
         * @var Request
         */
        private $request;

        /**
         * @var array
         */
        private $middleware = [];

        public function __construct(ContainerAdapter $container, ErrorHandlerInterface $error_handler)
        {

            $this->container = $container;
            $this->error_handler = $error_handler;
        }

        public function send(Request $request) : Pipeline
        {

            $this->request = $request;

            return $this;
        }

        /**
         * Set the array of middleware.
         *
         * Accepted: function ($request, Closure $next), Middleware::class , [Middleware ,
         * 'config_value'
         *
         * Middleware classes must implement Psr\Http\Server\MiddlewareInterface
         *
         */
        public function through(array $middleware) : Pipeline
        {

            $this->middleware = $this->normalizeMiddleware($middleware);

            return $this;

        }

        private function normalizeMiddleware(array $middleware) : array
        {

            return collect($middleware)
                ->map(function ($middleware) {

                    if ($middleware instanceof Closure) {

                        return new Delegate($middleware);
                    }

                    return $middleware;


                })
                ->map(function ($middleware) {

                    $middleware = Arr::wrap($middleware);

                    if ( ! in_array(MiddlewareInterface::class, class_implements($middleware[0]))) {

                        throw new ConfigurationException(
                            "Unsupported middleware type: {$middleware[0]})"
                        );

                    }

                    return $middleware;


                })
                ->map(function ($middleware) {

                    return $this->getMiddlewareAndParams($middleware);

                })
                ->all();

        }

        public function run($stack = null) : ResponseInterface
        {

            $stack = $stack ?? $this->buildMiddlewareStack();

            return $stack->handle($this->request);

        }

        public function then(Closure $request_handler) : ResponseInterface
        {

            $this->middleware[] = [new Delegate($request_handler), []];

            return $this->run($this->buildMiddlewareStack());


        }

        private function buildMiddlewareStack() : RequestHandlerInterface
        {

            return $this->nextMiddleware();

        }

        private function nextMiddleware() : Delegate
        {

            if ($this->middleware === []) {

                return new Delegate(function () {

                    throw new LogicException("Unresolved request: middleware stack exhausted with no result");

                });

            }

            return new Delegate(function (Request $request) {

                try {

                    return $this->resolveNextMiddleware($request);
                }
                catch (\Throwable $e) {

                    return $this->error_handler->transformToResponse($e);

                }


            });


        }

        private function resolveNextMiddleware(Request $request) : ResponseInterface
        {

            [$middleware, $constructor_args] = array_shift($this->middleware);

            if ($middleware instanceof MiddlewareInterface) {

                return $middleware->process($request, $this->nextMiddleware());

            }

            $constructor_args = $this->convertStringsToBooleans($constructor_args);

            $payload = new ReflectionPayload($middleware, $constructor_args);

            $middleware_instance = $this->container->make($middleware, $payload->build());

            return $middleware_instance->process($request, $this->nextMiddleware());

        }

        /**
         *
         * @param  array|string|object  $middleware_blueprint
         *
         * @return array
         */
        private function getMiddlewareAndParams($middleware_blueprint) : array
        {

            if (is_object($middleware_blueprint)) {

                return [$middleware_blueprint, []];

            }

            if (is_string($middleware_blueprint)) {

                return [$middleware_blueprint, []];

            }

            $middleware_class = array_shift($middleware_blueprint);

            $constructor_args = $middleware_blueprint;

            return [$middleware_class, $constructor_args];

        }

        private function convertStringsToBooleans(array $constructor_args) : array
        {

            return array_map(function ($value) {

                if ( ! is_string($value)) {

                    return $value;

                }

                if (strtolower($value) === 'true') {
                    return true;
                }
                if (strtolower($value) === 'false') {
                    return false;
                }

                return $value;

            }, $constructor_args);

        }

    }