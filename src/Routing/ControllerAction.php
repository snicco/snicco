<?php


	declare( strict_types = 1 );


	namespace WPMvc\Routing;

	use Closure;
    use Contracts\ContainerAdapter;
    use WPMvc\Contracts\ResolveControllerMiddleware;
	use WPMvc\Contracts\RouteAction;
    use WPMvc\Http\Controller;
    use WPMvc\Http\MiddlewareResolver;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\View\ViewFactory;

    class ControllerAction implements RouteAction, ResolveControllerMiddleware {


		/**
		 * @var array
		 */
		private $raw_callable;

		/**
		 * @var MiddlewareResolver
		 */
		private $middleware_resolver;

        /**
         * @var ContainerAdapter
         */
        private $container;

		public function __construct(array $raw_callable,  MiddlewareResolver $resolver, ContainerAdapter $container) {

			$this->raw_callable        = $raw_callable;
			$this->middleware_resolver = $resolver;
            $this->container = $container;

        }

		public function executeUsing(...$args) {

            $controller = $this->container->make($this->raw_callable[0]);

            if ( $controller instanceof Controller ) {

                $controller->giveResponseFactory($this->container->make(ResponseFactory::class));
                $controller->giveUrlGenerator($this->container->make(UrlGenerator::class));
                $controller->giveViewFactory($this->container->make(ViewFactory::class));

            }

            return $this->container->call([$controller, $this->raw_callable[1]], ...$args);

		}

		public function raw() : array
        {

			return $this->raw_callable;

		}

		public function resolveControllerMiddleware() : array {


			return $this->middleware_resolver->resolveFor($this->raw_callable);


		}

	}