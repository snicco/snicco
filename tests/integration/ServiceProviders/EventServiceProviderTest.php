<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use BetterWpHooks\Contracts\Dispatcher;
	use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\BodySent;
    use WPEmerge\Events\DoShutdown;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\MakingView;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\ExceptionHandling\ShutdownHandler;
	use WPEmerge\Http\HttpKernel;
	use WPEmerge\View\ViewService;


	class EventServiceProviderTest extends IntegrationTest {


		/** @test */
		public function the_event_service_provider_is_set_up_correctly() {

		    $this->newTestApp();

			/** @var WordpressDispatcher $d */
			$d = TestApp::resolve( Dispatcher::class );

			$this->dispatcher = $d;

			$this->assertInstanceOf( Dispatcher::class, $this->dispatcher );

			$this->assertHasListener([HttpKernel::class, 'run'], IncomingWebRequest::class);
			$this->assertHasListener([HttpKernel::class, 'run'], IncomingAdminRequest::class);
			$this->assertHasListener([HttpKernel::class, 'run'], IncomingAjaxRequest::class);
			$this->assertHasListener([HttpKernel::class, 'sendResponseDeferred'], AdminBodySendable::class);
			$this->assertHasListener([ShutdownHandler::class, 'shutdownWp'], BodySent::class);
			$this->assertHasListener([ShutdownHandler::class, 'unrecoverableException'], UnrecoverableExceptionHandled::class);
			$this->assertHasListener([ViewService::class, 'compose'], MakingView::class);
			$this->assertHasListener([HttpKernel::class, 'filterRequest'], WpQueryFilterable::class);
			$this->assertHasListener([ShutdownHandler::class, 'terminate'], DoShutdown::class);


		}


		private function assertHasListener (array $listener , string $event) {

            $this->assertTrue( $this->dispatcher->hasListenerFor($listener, $event ) );

        }


	}