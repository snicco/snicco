<?php


    declare(strict_types = 1);


    namespace Tests\integration;

    use Codeception\TestCase\WPTestCase;
    use Nyholm\Psr7\Request;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\HttpKernel;

    class IntegrationTest extends WPTestCase
    {

        public function newTestApp ( array $config = [] ) : Application
        {

            WP::reset();
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
            TestApp::setApplication(null);
            ApplicationEvent::setInstance(null);
            $app = TestApp::make();
            $app->boot($config);
            return $app;

        }

        protected function tearDown() : void
        {
            parent::tearDown();
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
            $GLOBALS['test'] = [];
        }

        protected function seeKernelOutput( $expected, $request ) {

            if ( ! $request instanceof IncomingRequest ) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            /** @var HttpKernel $kernel */
            $kernel = TestApp::resolve(HttpKernel::class);
            ob_start();
            $kernel->run($request);
            $this->assertSame($expected, ob_get_clean());

        }



    }