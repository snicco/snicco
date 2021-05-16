<?php


	declare( strict_types = 1 );


	namespace WPEmerge\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Events\MakingView;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\LoadedWpAdmin;
	use WPEmerge\ExceptionHandling\ShutdownHandler;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\View\ViewService;

	class EventServiceProvider extends ServiceProvider {

		private $mapped_events = [

			'template_include' => [ 'resolve', IncomingWebRequest::class, 3001 ],
			'admin_init'       => [ 'resolve', LoadedWpAdmin::class, 3001 ],

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

				ShutdownHandler::class . '@shutdownWp',

			],

			UnrecoverableExceptionHandled::class => [

				ShutdownHandler::class . '@exceptionHandled',

			],

			MakingView::class => [

				[ ViewService::class, 'compose' ],

			],

		];

		public function register() : void {

			ApplicationEvent::make( $this->container )
			                ->map( $this->mapped_events )
			                ->listeners( $this->event_listeners )
			                ->boot();

			$this->container->instance( Dispatcher::class, ApplicationEvent::dispatcher() );

		}

		public function bootstrap() : void {

			//

		}

	}