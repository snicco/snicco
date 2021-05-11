<?php


	declare( strict_types = 1 );


	namespace Tests\unit\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use Codeception\TestCase\WPTestCase;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Exceptions\ShutdownHandler;
	use WPEmerge\Http\HttpKernel;

	class EventServiceProviderTest extends WPTestCase {

		use BootApplication;

		/** @test */
		public function the_event_service_provider_is_set_up_correctly() {

			$app = $this->bootNewApplication();

			/** @var WordpressDispatcher $d */
			$d = $app->resolve( Dispatcher::class );

			$this->assertInstanceOf( WordpressDispatcher::class, $d );

			$this->assertTrue( $d->hasListenerFor( [
				HttpKernel::class,
				'handle',
			], IncomingWebRequest::class ) );
			$this->assertTrue( $d->hasListenerFor( [
				HttpKernel::class,
				'handle',
			], IncomingAdminRequest::class ) );
			$this->assertTrue( $d->hasListenerFor( [
				HttpKernel::class,
				'handle',
			], IncomingAjaxRequest::class ) );
			$this->assertTrue( $d->hasListenerFor( [
				HttpKernel::class,
				'sendBodyDeferred',
			], AdminBodySendable::class ) );
			$this->assertTrue( $d->hasListenerFor( [
				ShutdownHandler::class,
				'shutdownWp',
			], BodySent::class ) );
			$this->assertTrue( $d->hasListenerFor( [
				ShutdownHandler::class,
				'exceptionHandled',
			], UnrecoverableExceptionHandled::class ) );


		}



	}