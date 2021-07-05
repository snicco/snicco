<?php


    declare(strict_types = 1);


    namespace Tests;

    use Codeception\TestCase\WPTestCase;
    use PHPUnit\Framework\Assert;
    use Psr\Http\Message\ServerRequestInterface;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestMagicLink;
    use BetterWP\Application\Application;
    use BetterWP\Events\Event;
    use BetterWP\Contracts\ErrorHandlerInterface;
    use BetterWP\Contracts\MagicLink;
    use BetterWP\Contracts\RouteRegistrarInterface;
    use BetterWP\Contracts\ViewInterface;
    use BetterWP\Events\IncomingRequest;
    use BetterWP\Events\IncomingWebRequest;
    use BetterWP\Events\ResponseSent;
    use BetterWP\Testing\TestingErrorHandler;
    use BetterWP\Support\WP;
    use BetterWP\Http\HttpKernel;
    use BetterWP\Http\Psr7\Request;

    use BetterWP\Support\Arr;

    use function do_action;

    class IntegrationTest extends WPTestCase
    {

        protected $send_emails = [];

        protected function catchMail () {

            add_filter('pre_wp_mail', [$this, 'emailContents'], 10, 3);

        }

        public function emailContents($null, $attributes) : bool
        {

            $this->send_emails = $attributes;

            return true;


        }

        protected function loadRoutes () {

            $registrar = TestApp::resolve(RouteRegistrarInterface::class);
            $registrar->loadApiRoutes(TestApp::config());
            $registrar->loadStandardRoutes(TestApp::config());
            $registrar->loadIntoRouter();

        }

        protected function loadApiRoutes () {
            $registrar = TestApp::resolve(RouteRegistrarInterface::class);
            $registrar->loadStandardRoutes(TestApp::config());
            $registrar->loadIntoRouter();
        }

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
            Event::setInstance(null);

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
            HeaderStack::reset();
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

            Arr::set($config, 'routing.trailing_slash', false);

            if ( $with_exceptions ) {

                if ( null === Arr::get($config,'exception_handling.enabled', null ) ) {

                    Arr::set($config, 'exception_handling.enabled', true);

                }


            }

            $app->boot($config);

            if ( ! $with_exceptions ) {

                $this->withoutExceptionHandling();

            }

            // $app->container()->instance(MagicLink::class, new TestMagicLink());

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

        protected function registerAndRunApiRoutes() {
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

        protected function withSession(Request $request, array $session_data ) {

            $session = TestApp::session();
            $array_handler = $session->getDriver();

            $data = [];

            foreach ($session_data as $key =>  $value) {

                Arr::set($data, $key, $value);

            }

            $array_handler->write($this->hashedSessionId(), serialize($data));

            return $request->withAddedHeader('Cookie', 'wp_mvc_session='.$this->testSessionId() );

        }

        protected function fakeResponseSending(array $events = []) {

            Event::fake(array_merge([ResponseSent::class], $events));

        }


    }