<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use LogicException;
use ReflectionProperty;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Event\HandledRequest;
use Snicco\Bundle\HttpRouting\Event\HandlingRequest;
use Snicco\Bundle\HttpRouting\Event\ResponseSent;
use Snicco\Bundle\HttpRouting\Event\TerminatedResponse;
use Snicco\Bundle\HttpRouting\HttpKernelRunner;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\ResponseEmitter\LaminasEmitterStack;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Controller\HttpRunnerTestController;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function add_action;
use function did_action;
use function dirname;
use function do_action;
use function remove_all_filters;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 *
 * @internal
 */
final class HttpKernelRunnerTest extends WPTestCase
{
    use BundleTestHelpers;

    private Kernel $kernel;

    private array $_get;

    private array $_server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();

        $_get = $_GET;
        $_server = $_SERVER;
        $this->_get = $_get;
        $this->_server = $_server;
        $this->kernel = new Kernel(new PimpleContainerAdapter(), Environment::testing(), $this->directories, );

        remove_all_filters('admin_init');
        remove_all_filters('all_admin_notices');
        if (did_action('plugins_loaded')) {
            /**
             * @psalm-suppress MixedArrayAccess
             * @psalm-suppress InvalidArrayOffset
             */
            unset($GLOBALS['wp_actions']['plugins_loaded']);
        }
    }

    protected function tearDown(): void
    {
        $_GET = $this->_get;
        $_SERVER = $this->_server;

        $this->bundle_test->tearDownDirectories();

        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_http_runner_can_be_resolved_in_production(): void
    {
        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::prod(), $this->directories, );
        $kernel->boot();
        $this->assertInstanceOf(HttpKernelRunner::class, $kernel->container()->make(HttpKernelRunner::class));
    }

    /**
     * @test
     */
    public function test_frontend_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/frontend';

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched(
            'test_emitter',
            fn (Response $response): bool => RoutingBundleTestController::class === (string) $response->getBody()
                                            && ! $response instanceof DelegatedResponse
        );
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_delegated_responses_with_headers(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/bogus';

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => ! $event->body_sent);

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $body = (string) $response->getBody();
            $this->assertSame('', $body);
            $this->assertInstanceOf(DelegatedResponse::class, $response);

            return true;
        });
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_delegated_responses_without_headers(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/no-response';

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);

        $dispatcher->assertNotDispatched(ResponseSent::class);
        $dispatcher->assertNotDispatched('test_emitter');
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_admin_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=foo';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(ResponseSent::class);
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_admin_requests_with_custom_hook(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=foo';

        $this->httpKernelRunner()
            ->listen(true, 'wp_loaded', 'init', 'init');

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(ResponseSent::class);
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_admin_request_only_emits_headers_right_away(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=foo';
        $_GET['page'] = 'foo';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertFalse($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('', (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());

            return true;
        });
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function the_body_of_admin_requests_is_dispatched_at_the_right_moment(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=foo';
        $_GET['page'] = 'foo';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertFalse($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('', (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());

            return true;
        });
        $dispatcher->assertNotDispatched(TerminatedResponse::class);

        $this->expectOutputString(HttpRunnerTestController::class);

        do_action('all_admin_notices');

        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_admin_request_that_completely_delegates(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=do_nothing';
        $_GET['page'] = 'do_nothing';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);
        $dispatcher->assertNotDispatched('test_emitter');
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function redirects_are_sent_immediately_for_admin_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=admin_redirect';
        $_GET['page'] = 'admin_redirect';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertTrue($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('', (string) $response->getBody());
            $this->assertSame('/foo', $response->getHeaderLine('location'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function client_errors_are_sent_immediately_for_admin_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=client_error';
        $_GET['page'] = 'client_error';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertTrue($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('no way', (string) $response->getBody());
            $this->assertSame(403, $response->getStatusCode());
            $this->assertTrue($response->hasHeader('content-length'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function server_errors_are_sent_immediately_for_admin_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=server_error';
        $_GET['page'] = 'server_error';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertTrue($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('server error', (string) $response->getBody());
            $this->assertSame(500, $response->getStatusCode());
            $this->assertTrue($response->hasHeader('content-length'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function the_content_length_header_is_removed_for_admin_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=foo';
        $_GET['page'] = 'foo';

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event): bool {
            $this->assertFalse($event->body_sent);

            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame('', (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());
            $this->assertFalse($response->hasHeader('content-length'));
            $this->assertFalse($response->hasHeader('Content-length'));

            return true;
        });

        $this->expectOutputString(HttpRunnerTestController::class);

        do_action('all_admin_notices');
    }

    /**
     * @test
     */
    public function test_api_requests_are_run_on_init_if_the_path_matches(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/snicco/auth/register';

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame(HttpRunnerTestController::class, (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());
            $this->assertTrue($response->hasHeader('content-length'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_run_sends_a_frontend_response_immediately(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/frontend';

        $this->kernel->boot();

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        $this->httpKernelRunner()
            ->run();

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched(
            'test_emitter',
            fn (Response $response): bool => RoutingBundleTestController::class === (string) $response->getBody()
                                            && ! $response instanceof DelegatedResponse
        );
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_run_sends_an_api_response_immediately(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/snicco/auth/register';

        $this->kernel->boot();

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        $this->httpKernelRunner()
            ->run();

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame(HttpRunnerTestController::class, (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());
            $this->assertTrue($response->hasHeader('content-length'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_laminas_is_used_in_non_testing_env(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/frontend';

        $kernel = new Kernel(new PimpleContainerAdapter(), Environment::dev(), $this->directories, );
        $kernel->boot();

        $http_runner = $kernel->container()
            ->make(HttpKernelRunner::class);

        $property = new ReflectionProperty(HttpKernelRunner::class, 'emitter');
        $property->setAccessible(true);

        $emitter = $property->getValue($http_runner);

        $this->assertInstanceOf(LaminasEmitterStack::class, $emitter);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_http_kernel_runner_listens_after_plugins_loaded(): void
    {
        $this->expectException(LogicException::class);

        do_action('plugins_loaded');

        $this->httpKernelRunner()
            ->listen(false);
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_http_kernel_runner_runs_after_plugins_loaded(): void
    {
        $this->expectException(LogicException::class);

        do_action('plugins_loaded');

        $this->httpKernelRunner()
            ->run();
    }

    /**
     * @test
     */
    public function than_no_exception_is_thrown_if_http_kernel_runner_is_used_within_plugins_loaded(): void
    {
        $called = false;

        add_action('plugins_loaded', function () use (&$called) {
            $this->httpKernelRunner()
                ->listen(false);
            $called = true;
        });

        do_action('plugins_loaded');

        $this->assertTrue($called);
    }

    /**
     * @test
     */
    public function that_multiple_early_route_prefixes_can_be_used_and_dispatch_an_api_request(): void
    {
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::API_PREFIX, '/api-base');
            $config->set('routing.' . RoutingOption::EARLY_ROUTES_PREFIXES, [
                '/api-base/auth',
                '/api-base/totp',
            ]);
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api-base/totp/register';

        $this->httpKernelRunner()
            ->listen(false, 'wp', 'plugins_loaded');

        do_action('plugins_loaded');

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event) => ! $event->response instanceof DelegatedResponse);

        $dispatcher->resetDispatchedEvents();

        $_SERVER['REQUEST_URI'] = '/api-base/auth/register';

        do_action('plugins_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event) => ! $event->response instanceof DelegatedResponse);
    }

    /**
     * @test
     */
    public function that_multiple_api_prefixes_dont_dispatch_api_request_for_non_matches(): void
    {
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::API_PREFIX, '/api-base');
            $config->set('routing.' . RoutingOption::EARLY_ROUTES_PREFIXES, [
                '/api-base/auth',
                '/api-base/totp',
            ]);
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api-base/whatever/register';

        $this->httpKernelRunner()
            ->listen(false, 'wp', 'plugins_loaded');

        do_action('plugins_loaded');

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);
    }

    /**
     * @test
     */
    public function test_frontend_requests_with_relative_routing_file_configuration(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/frontend';

        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::ROUTE_DIRECTORIES, ['routes']);
        });

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched(
            'test_emitter',
            fn (Response $response): bool => RoutingBundleTestController::class === (string) $response->getBody()
                && ! $response instanceof DelegatedResponse
        );
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_admin_requests_with_relative_routing_file_configuration(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=foo';

        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::ROUTE_DIRECTORIES, ['routes']);
        });

        $this->httpKernelRunner()
            ->listen(true);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(ResponseSent::class);
        $dispatcher->assertNotDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_api_requests_with_relative_routing_file_configuration(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/snicco/auth/register';

        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::API_ROUTE_DIRECTORIES, ['routes/api']);
        });

        $this->httpKernelRunner()
            ->listen(false);

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()
            ->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(fn (ResponseSent $event): bool => $event->body_sent);

        $dispatcher->assertDispatched('test_emitter', function (Response $response): bool {
            $this->assertSame(HttpRunnerTestController::class, (string) $response->getBody());
            $this->assertNotEmpty($response->getHeaders());
            $this->assertTrue($response->hasHeader('content-length'));

            return true;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

    private function httpKernelRunner(): HttpKernelRunner
    {
        if (! $this->kernel->booted()) {
            $this->kernel->boot();
        }

        return $this->kernel->container()
            ->make(HttpKernelRunner::class);
    }
}
