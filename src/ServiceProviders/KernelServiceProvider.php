<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Middleware\CsrfProtection;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\Middleware\StartSession;
	use WPEmerge\Middleware\SubstituteBindings;
	use WPEmerge\Middleware\Authorize;
	use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Middleware\RedirectIfAuthenticated;
	use WPEmerge\Routing\Router;



	class KernelServiceProvider extends ServiceProvider {


		public function register() :void  {


			$this->config->extend('middleware', [

				'csrf'      => CsrfProtection::class,
				'auth'      => Authenticate::class,
				'guest'     => RedirectIfAuthenticated::class,
				'can'       => Authorize::class,

			]);

			$this->config->extend( 'middleware_groups', [

				'global' => [],
				'web'    => [],
				'ajax'   => [],
				'admin'  => [],

			] );

			$this->config->extend('middleware_priority', [

				StartSession::class,
				SubstituteBindings::class,

			]);

			$this->container->singleton(HttpKernel::class,  function () {

				$kernel = new HttpKernel(

					$this->container[ Router::class ],
					$this->container,
					$this->container[ErrorHandlerInterface::class]

				);

				$kernel->setRouteMiddlewareAliases( $this->config['middleware'] );
				$kernel->setMiddlewareGroups( $this->config['middleware_groups'] );
				$kernel->setMiddlewarePriority( $this->config['middleware_priority'] );

				if( $this->config->get(ApplicationServiceProvider::STRICT_MODE, false)) {

					$kernel->runInTakeoverMode();

				}


				return $kernel;

			});



		}


		public function bootstrap() :void {

			// Nothing to bootstrap.

		}

	}
