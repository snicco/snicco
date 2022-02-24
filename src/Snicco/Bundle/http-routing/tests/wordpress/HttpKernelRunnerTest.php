<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use ReflectionProperty;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\Event\HandledRequest;
use Snicco\Bundle\HttpRouting\Event\HandlingRequest;
use Snicco\Bundle\HttpRouting\Event\ResponseSent;
use Snicco\Bundle\HttpRouting\Event\TerminatedResponse;
use Snicco\Bundle\HttpRouting\HttpKernelRunner;
use Snicco\Bundle\HttpRouting\ResponseEmitter\LaminasEmitterStack;
use Snicco\Bundle\HttpRouting\Tests\wordpress\fixtures\Controller\HttpRunnerTestController;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function do_action;
use function remove_all_filters;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class HttpKernelRunnerTest extends WPTestCase
{

    private Kernel $kernel;
    private HttpKernelRunner $http_dispatcher;
    private array $_get;
    private array $_server;

    protected function setUp(): void
    {
        parent::setUp();
        $_get = $_GET;
        $_server = $_SERVER;
        $this->_get = $_get;
        $this->_server = $_server;
        $this->kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::testing(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );
        $this->kernel->boot();
        $this->http_dispatcher = $this->kernel->container()->make(HttpKernelRunner::class);
        remove_all_filters('admin_init');
        remove_all_filters('all_admin_notices');
    }

    protected function tearDown(): void
    {
        $_GET = $this->_get;
        $_SERVER = $this->_server;
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_http_runner_can_be_resolved_in_production(): void
    {
        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::prod(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );
        $kernel->boot();
        $this->assertInstanceOf(
            HttpKernelRunner::class,
            $kernel->container()->make(HttpKernelRunner::class)
        );
    }

    /**
     * @test
     */
    public function test_frontend_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/frontend1';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(false);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->body_sent;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            return HttpRunnerTestController::class === (string)$response->getBody()
                && !$response instanceof DelegatedResponse;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_delegated_responses_with_headers(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/bogus';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(false);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('wp_loaded');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->body_sent === false;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $body = (string)$response->getBody();
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(false);

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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

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
    public function test_admin_request_only_emits_headers_right_away(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php';
        $_SERVER['QUERY_STRING'] = 'page=foo';
        $_GET['page'] = 'foo';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertFalse($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('', (string)$response->getBody());
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertFalse($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('', (string)$response->getBody());
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertTrue($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('', (string)$response->getBody());
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertTrue($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('no way', (string)$response->getBody());
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertTrue($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('server error', (string)$response->getBody());
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

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(true);

        do_action('admin_init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            $this->assertFalse($event->body_sent);
            return true;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame('', (string)$response->getBody());
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
        $_SERVER['REQUEST_URI'] = '/sniccowp/auth/register';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $this->http_dispatcher->listen(false);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        do_action('init');

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->body_sent;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame(HttpRunnerTestController::class, (string)$response->getBody());
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
        $_SERVER['REQUEST_URI'] = '/frontend1';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        $this->http_dispatcher->run();

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->body_sent;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            return HttpRunnerTestController::class === (string)$response->getBody()
                && !$response instanceof DelegatedResponse;
        });
        $dispatcher->assertDispatched(TerminatedResponse::class);
    }

    /**
     * @test
     */
    public function test_run_sends_an_api_response_immediately(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/sniccowp/auth/register';

        /** @var TestableEventDispatcher $dispatcher */
        $dispatcher = $this->kernel->container()->make(TestableEventDispatcher::class);

        $dispatcher->assertNotDispatched(HandlingRequest::class);
        $dispatcher->assertNotDispatched(HandledRequest::class);
        $dispatcher->assertNotDispatched(ResponseSent::class);

        $this->http_dispatcher->run();

        $dispatcher->assertDispatched(HandlingRequest::class);
        $dispatcher->assertDispatched(HandledRequest::class);
        $dispatcher->assertDispatched(function (ResponseSent $event) {
            return $event->body_sent;
        });

        $dispatcher->assertDispatched('test_emitter', function (Response $response) {
            $this->assertSame(HttpRunnerTestController::class, (string)$response->getBody());
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
        $_SERVER['REQUEST_URI'] = '/frontend1';

        $kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::dev(),
            Directories::fromDefaults(__DIR__ . '/fixtures')
        );
        $kernel->boot();

        $http_runner = $kernel->container()->make(HttpKernelRunner::class);

        $property = new ReflectionProperty(HttpKernelRunner::class, 'emitter');
        $property->setAccessible(true);
        $emitter = $property->getValue($http_runner);

        $this->assertInstanceOf(LaminasEmitterStack::class, $emitter);
    }

}