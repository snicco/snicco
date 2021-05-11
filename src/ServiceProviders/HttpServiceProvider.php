<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ResponseFactoryInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewServiceInterface;
	use WPEmerge\Http\ResponseFactory;
	use WPEmerge\Middleware\CsrfProtection;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Middleware\StartSession;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Middleware\Authorize;
	use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Middleware\RedirectIfAuthenticated;
	use WPEmerge\Routing\Router;



	class HttpServiceProvider extends ServiceProvider {


		public function register() :void  {


			$this->config->extend('middleware.aliases', [

				'csrf'      => CsrfProtection::class,
				'auth'      => Authenticate::class,
				'guest'     => RedirectIfAuthenticated::class,
				'can'       => Authorize::class,

			]);

			$this->config->extend( 'middleware.groups', [

				'global' => [],
				'web'    => [],
				'ajax'   => [],
				'admin'  => [],

			] );

			$this->config->extend('middleware.priority', [

				StartSession::class,
				SubstituteBindings::class,

			]);

			$this->container->singleton(HttpKernel::class,  function () {

				return new HttpKernel(

					$this->container[ Router::class ],
					$this->container,
					$this->container[ErrorHandlerInterface::class]

				);


			});

			$this->container->singleton(ResponseFactoryInterface::class, function () {

				return new ResponseFactory($this->container->make(ViewServiceInterface::class));

			});

		}


		public function bootstrap() :void {

			$kernel = $this->container->make(HttpKernel::class);

			$kernel->setRouteMiddlewareAliases( $this->config->get('middleware.aliases', [] ) );
			$kernel->setMiddlewareGroups( $this->config->get('middleware.groups', [] ) );
			$kernel->setMiddlewarePriority( $this->config->get('middleware.priority', [] ) );

			if( $this->config->get(ApplicationServiceProvider::STRICT_MODE, false)) {

				$kernel->runInTakeoverMode();

			}


		}

	}
