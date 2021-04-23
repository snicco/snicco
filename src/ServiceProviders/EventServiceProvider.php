<?php


	namespace WPEmerge\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use Contracts\ContainerAdapter;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\StartedLoadingWpAdmin;
	use WPEmerge\AjaxShutdownHandler;
	use WPEmerge\DynamicHooksFactory;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\ResponseSent;
	use WPEmerge\Events\RouteMatched;
	use WPEmerge\Events\SendBodySeparately;
	use WPEmerge\Kernels\HttpKernel;
	use WPEmerge\RouteMatcher;

	class EventServiceProvider implements ServiceProviderInterface {

		private $mapped_events = [

			'template_include' => [ [ IncomingWebRequest::class, 3001 ] ],
			'admin_init'       => [ [ StartedLoadingWpAdmin::class, 3001 ] ],

		];

		private $event_listeners = [

			IncomingWebRequest::class => [

				RouteMatcher::class . '@handleRequest',

			],

			IncomingAdminRequest::class => [

				RouteMatcher::class . '@handleRequest',

			],

			IncomingAjaxRequest::class => [

				RouteMatcher::class . '@handleRequest',

			],

			StartedLoadingWpAdmin::class => [

				DynamicHooksFactory::class . '@handleEvent',

			],

			AdminBodySendable::class => [

				RouteMatcher::class . '@sendAdminBodySeparately',

			],

			RouteMatched::class => [

				HttpKernel::class . '@handle',

			],

			SendBodySeparately::class => [

				HttpKernel::class . '@sendBody',

			],

			ResponseSent::class => [

				AjaxShutdownHandler::class . '@shutdownWp',

			],

		];


		public function register( ContainerAdapter $container ) {

			$container->singleton( 'mapped.events', function () {

				return $this->mapped_events;

			} );

			$container->singleton( 'event.listeners', function () {

				return $this->event_listeners;

			} );

			$container->singleton(Dispatcher::class, function () {

				return ApplicationEvent::dispatcher();

			});

		}

		public function bootstrap( ContainerAdapter $container ) {

			ApplicationEvent::make( $container )
			                ->map( $container->make( 'mapped.events' ) )
			                ->listeners( $container->make( 'event.listeners' ) )
			                ->boot();

		}

	}