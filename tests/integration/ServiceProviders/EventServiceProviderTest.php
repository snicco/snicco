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
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Events\StartLoadingAdminFooter;
    use WPEmerge\Events\WpQueryFilterable;
    use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingAjaxRequest;
	use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\MakingView;
	use WPEmerge\Events\UnrecoverableExceptionHandled;
	use WPEmerge\ExceptionHandling\ShutdownHandler;
	use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Core\OutputBufferMiddleware;
    use WPEmerge\Listeners\FilterWpQuery;
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
            $this->assertHasListener([HttpKernel::class, 'run'], AdminBodySendable::class);
            $this->assertHasListener([HttpKernel::class, 'run'], IncomingAjaxRequest::class);

            $this->assertHasListener([OutputBufferMiddleware::class, 'start'], IncomingAdminRequest::class);
            $this->assertHasListener([OutputBufferMiddleware::class, 'flush'], StartLoadingAdminFooter::class);

            $this->assertHasListener([ShutdownHandler::class, 'handle'], ResponseSent::class);
			$this->assertHasListener([ShutdownHandler::class, 'unrecoverableException'], UnrecoverableExceptionHandled::class);

			$this->assertHasListener([ViewService::class, 'compose'], MakingView::class);
			$this->assertHasListener([FilterWpQuery::class, 'handle'], WpQueryFilterable::class);


		}


		private function assertHasListener (array $listener , string $event) {

            $this->assertTrue( $this->dispatcher->hasListenerFor($listener, $event ) );

        }


	}