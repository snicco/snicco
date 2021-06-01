<?php


    declare(strict_types = 1);


    namespace Tests\integration;

    use Codeception\TestCase\WPTestCase;
    use Nyholm\Psr7\Request;
    use Tests\stubs\HeaderStack;
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

        protected function setUp() : void
        {

            parent::setUp();

            HeaderStack::reset();

            WP::reset();
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
            TestApp::setApplication(null);
            ApplicationEvent::setInstance(null);

            $this->afterSetup();

        }

        protected function afterSetup()
        {
            //
        }

        protected function beforeTearDown()
        {
            //
        }

        protected function tearDown() : void
        {

            $this->beforeTearDown();

            parent::tearDown();
            $GLOBALS['wp_filter'] = [];
            $GLOBALS['wp_actions'] = [];
            $GLOBALS['wp_current_filter'] = [];
            $GLOBALS['test'] = [];
        }

        public function newTestApp(array $config = []) : Application
        {

            $app = TestApp::make();
            $app->boot($config);

            return $app;

        }

        protected function runKernel($request)
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            /** @var HttpKernel $kernel */
            $kernel = TestApp::resolve(HttpKernel::class);
            ob_start();
            $kernel->run($request);

            return ob_get_clean();

        }

        protected function seeKernelOutput($expected, $request)
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            $this->assertSame( strtolower($expected), strtolower($this->runKernel($request)));

        }

        protected function assertOutputContains($expected, $request,string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            $this->assertStringContainsString(strtolower($expected), strtolower($this->runKernel($request)), $message);

        }

        protected function assertOutputNotContains($expected, $request, string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            $this->assertStringNotContainsString($expected, $this->runKernel($request), $message);

        }

        protected function assertOutput($expected, $request, string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest('wordpress.php', $request);

            }

            $this->assertSame($expected, $this->runKernel($request), $message);

        }



    }