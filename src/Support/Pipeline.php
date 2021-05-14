<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    use Closure;
    use Contracts\ContainerAdapter;
    use LogicException;
    use mindplay\readable;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Server\MiddlewareInterface;
    use Psr\Http\Server\RequestHandlerInterface;
    use Throwable;
    use WPEmerge\Exceptions\ConfigurationException;
    use WPEmerge\Routing\Delegate;
    use WPEmerge\Traits\ReflectsCallable;

    class Pipeline
    {

        use ReflectsCallable;

        /**
         * The container implementation.
         *
         * @var ContainerAdapter
         */
        private $container;

        /**
         *
         * @var ServerRequestInterface
         */
        private $request;

        /**
         * @var array
         */
        private $middleware = [];

        public function __construct(ContainerAdapter $container)
        {

            $this->container = $container;
        }

        public function send(ServerRequestInterface $request) : Pipeline
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


        public function then(Closure $request_handler) : ResponseInterface
        {

            $this->middleware[] = [ new Delegate($request_handler), [] ];

            return $this->run($this->buildMiddlewareStack());


        }

        private function run($stack)
        {

            return $stack->handle($this->request);

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

            return new Delegate(function (ServerRequestInterface $request) {

                [$middleware, $constructor_args] = array_shift($this->middleware);

                if ( $middleware instanceof MiddlewareInterface ) {

                    return $middleware->process($request, $this->nextMiddleware());

                }

                /** @var MiddlewareInterface $middleware_instance */
                $middleware_instance = $this->container->make(
                    $middleware,
                    $this->buildNamedConstructorArgs($middleware, $constructor_args)
                );

                return $middleware_instance->process($request, $this->nextMiddleware());

            });


        }

        private function returnIfValid($response, $middleware) : ResponseInterface
        {

            if ( ! $response instanceof ResponseInterface) {

                $class = get_class($middleware);

                throw new LogicException("invalid middleware result returned by: {$class}");

            }

            return $response;

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


    }