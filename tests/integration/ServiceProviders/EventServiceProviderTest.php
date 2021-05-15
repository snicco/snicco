<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use BetterWpHooks\Dispatchers\WordpressDispatcher;
	use Codeception\TestCase\WPTestCase;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\MakingView;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\Exceptions\ShutdownHandler;
	use WPEmerge\Facade\WP;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\ServiceProviders\EventServiceProvider;
	use WPEmerge\View\ViewService;


	class EventServiceProviderTest extends WPTestCase {

		use BootServiceProviders;

		public function neededProviders() : array {

			return [
				EventServiceProvider::class,
			];
		}

		protected function tearDown() : void {

			WP::clearResolvedInstances();

		}


		/** @test */
		public function the_event_service_provider_is_set_up_correctly() {

			/** @var WordpressDispatcher $d */
			$d = $this->app->resolve( Dispatcher::class );

			$this->dispatcher = $d;

			$this->assertInstanceOf( Dispatcher::class, $this->dispatcher );

			$this->assertHasListener([HttpKernel::class, 'handle'], IncomingWebRequest::class);
			$this->assertHasListener([HttpKernel::class, 'handle'], IncomingAdminRequest::class);
			$this->assertHasListener([HttpKernel::class, 'handle'], IncomingAjaxRequest::class);
			$this->assertHasListener([HttpKernel::class, 'sendBodyDeferred'], AdminBodySendable::class);
			$this->assertHasListener([ShutdownHandler::class, 'shutdownWp'], BodySent::class);
			$this->assertHasListener([ShutdownHandler::class, 'exceptionHandled'], UnrecoverableExceptionHandled::class);
			$this->assertHasListener([ViewService::class, 'compose'], MakingView::class);


			ApplicationEvent::fake();

			IncomingWebRequest::dispatch(['wordpress.php', TestRequest::from('GET', 'foo')]);
			IncomingAdminRequest::dispatch([TestRequest::from('GET', 'foo')]);
            IncomingAjaxRequest::dispatch([TestRequest::from('GET', 'foo')]);


		}



		private function assertHasListener (array $listener , string $event) {

            $this->assertTrue( $this->dispatcher->hasListenerFor($listener, $event ) );

        }


	}