<?php


	namespace WPEmerge\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use Contracts\ContainerAdapter;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\LoadedWpAdmin;
	use WPEmerge\AjaxShutdownHandler;
	use WPEmerge\Factories\DynamicHooksFactory;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Http\HttpKernel;

	class EventServiceProvider extends ServiceProvider {

		private $mapped_events = [

			'template_include' =>  [ 'resolve', IncomingWebRequest::class, 3001 ],
			'admin_init'       =>  [ 'resolve', LoadedWpAdmin::class, 3001  ],

		];

		private $event_listeners = [

			IncomingWebRequest::class => [

				HttpKernel::class . '@handle',

			],

			IncomingAdminRequest::class => [

				HttpKernel::class . '@handle',

			],

			IncomingAjaxRequest::class => [

				HttpKernel::class . '@handle',

			],

			AdminBodySendable::class => [

				HttpKernel::class . '@sendBodyDeferred',

			],

			BodySent::class => [

				AjaxShutdownHandler::class . '@shutdownWp',

			],

		];

		public function register() :void  {

			/** @todo change these to instance() calss  */
			$this->container->singleton( 'mapped.events', function () {

				return $this->mapped_events;

			} );

			$this->container->singleton( 'event.listeners', function () {

				return $this->event_listeners;

			} );

			$this->container->singleton( Dispatcher::class, function () {

				return ApplicationEvent::dispatcher();

			} );

		}

		public function bootstrap() :void  {

			ApplicationEvent::make( $this->container )
			                ->map( $this->container->make( 'mapped.events' ) )
			                ->listeners( $this->container->make( 'event.listeners' ) )
			                ->boot();

		}

	}