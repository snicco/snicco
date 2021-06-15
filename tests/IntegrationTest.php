<?php


    declare(strict_types = 1);


    namespace Tests;

    use Codeception\TestCase\WPTestCase;
    use PHPUnit\Framework\Assert;
    use Psr\Http\Message\ServerRequestInterface;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestMagicLink;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\ExceptionHandling\TestingErrorHandler;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;

    use function do_action;

    class IntegrationTest extends WPTestCase
    {

        protected function assertViewContent(string $expected,  $actual) {

            $actual = ($actual instanceof ViewInterface) ? $actual->toString() :$actual;

            $actual = preg_replace( "/\r|\n|\s{2,}/", "", $actual );

            Assert::assertSame($expected, trim($actual), 'View not rendered correctly.');

        }

        protected function withoutExceptionHandling () {

            TestApp::container()->instance(ErrorHandlerInterface::class, new TestingErrorHandler());

        }

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

        protected function newTestApp(array $config = [], bool $with_exceptions = false) : Application
        {

            $app = TestApp::make();
            $app->runningUnitTest();
            $app->boot($config);

            $app->container()->instance(MagicLink::class, new TestMagicLink());

            if ( ! $with_exceptions ) {

                $this->withoutExceptionHandling();

            }

            return $app;

        }

        protected function runKernel($request)
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest($request, 'wordpress.php');

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

                $request = new IncomingWebRequest($request, 'wordpress.php');

            }

            $this->assertSame( strtolower($expected), strtolower($this->runKernel($request)));

        }

        protected function assertOutputContains($expected, $request,string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest($request, 'wordpress.php');


            }

            $this->assertStringContainsString(strtolower($expected), strtolower($this->runKernel($request)), $message);

        }

        protected function assertOutputNotContains($expected, $request, string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest($request, 'wordpress.php');


            }

            $this->assertStringNotContainsString($expected, $this->runKernel($request), $message);

        }

        protected function assertOutput($expected, $request, string $message = '')
        {

            if ( ! $request instanceof IncomingRequest) {

                $request = new IncomingWebRequest($request, 'wordpress.php');


            }

            $this->assertSame($expected, $this->runKernel($request), $message);

        }

        protected function rebindRequest(Request $request) {

            $c = TestApp::container();
            $c->instance(ServerRequestInterface::class, $request);
            $c->instance(Request::class, $request);

        }

        protected function registerRoutes() {
            do_action('init');
        }

        protected function makeFallbackConditionPass () {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;

        }

        protected function testSessionId () : string
        {
            // Dont change the strength of the tokens for tests unless a different strength is provided
            // for the session store than the default 32 bytes.
            return str_repeat('a', 64);

    }

        protected function fakeResponseSending(array $events = []) {

            ApplicationEvent::fake(array_merge([ResponseSent::class], $events));

        }

    }