<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session\Tests\Middleware;

use LogicException;
use Psr\Log\Test\TestLogger;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Testing\AssertableCookie;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\ImmutableSession;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\SessionManager\SessionManger;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\ReadOnlySession;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;

use function array_merge;
use function time;
use function urldecode;
use function urlencode;

/**
 * @internal
 */
final class StatefulRequestTest extends MiddlewareTestCase
{
    private InMemoryDriver $session_driver;

    private TestLogger $logger;

    /**
     * @var array{
     *     cookie_name:string,
     *     idle_timeout_in_sec: positive-int,
     *     rotation_interval_in_sec: positive-int,
     *     garbage_collection_percentage: int,
     *     absolute_lifetime_in_sec?: positive-int,
     *     domain?: string,
     *     same_site: 'Lax'|'Strict'|'None',
     *     path:string,
     *     http_only: bool,
     *     secure: bool
     * }
     */
    private array $default_config = [
        'path' => '/',
        'cookie_name' => 'test_cookie',
        'http_only' => true,
        'secure' => true,
        'same_site' => 'Lax',
        'idle_timeout_in_sec' => 60 * 15,
        'rotation_interval_in_sec' => 60 * 10,
        'garbage_collection_percentage' => 0,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session_driver = new InMemoryDriver();
        $this->logger = new TestLogger();
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_the_request_path_does_not_match_the_session_cookie_path(): void
    {
        try {
            $middleware = $this->getMiddleware('/wp-admin');
            $this->runMiddleware($middleware, $this->frontendRequest('/foo'));
            $this->fail('The session middleware should throw an exception.');
        } catch (LogicException $e) {
            $this->assertStringStartsWith(
                'The request path [/foo] is not compatible with your session cookie path [/wp-admin].',
                $e->getMessage()
            );
        }

        try {
            $middleware = $this->getMiddleware('/foo/bar/baz');
            $this->runMiddleware($middleware, $this->frontendRequest('/foo/baz/baz'));
            $this->fail('The session middleware should throw an exception.');
        } catch (LogicException $e) {
            $this->assertStringStartsWith(
                'The request path [/foo/baz/baz] is not compatible with your session cookie path [/foo/bar/baz].',
                $e->getMessage()
            );
        }

        $middleware = $this->getMiddleware('/foo/bar');
        $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar/baz'))
            ->assertNextMiddlewareCalled();
        $this->runMiddleware($middleware, $this->frontendRequest('/foo/bar/bam'))
            ->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function a_session_is_started_without_existing_cookie(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware($m, $this->frontendRequest());

        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $session);
        $this->assertInstanceOf(ReadOnlySession::class, $session);
    }

    /**
     * @test
     */
    public function a_session_is_started_from_an_existing_cookie(): void
    {
        $manager = new SessionManger(
            SessionConfig::fromDefaults('test_cookie'),
            $this->session_driver,
            new JsonSerializer()
        );
        $session = $manager->start(new CookiePool([]));
        $session->put('foo', 'bar');

        $manager->save($session);

        $this->assertCount(1, $this->session_driver->all());

        $request = $this->frontendRequest()
            ->withCookieParams([
                'test_cookie' => $session->id()
                    ->asString(),
            ]);
        $response = $this->runMiddleware($this->getMiddleware(), $request);
        $response->assertNextMiddlewareCalled();

        $received_session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $received_session);
        $this->assertSame('bar', $received_session->get('foo'));
    }

    /**
     * @test
     */
    public function read_verbs_have_only_access_to_a_read_only_session(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware($m, $this->frontendRequest());

        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $session);
        $this->assertInstanceOf(ReadOnlySession::class, $session);
        $this->assertNotInstanceOf(MutableSession::class, $session);
        $this->assertNull($this->receivedRequest()->getAttribute(MutableSession::class));
    }

    /**
     * @test
     */
    public function non_read_requests_have_access_to_a_write_session(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));

        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $session);
        $this->assertInstanceOf(ReadOnlySession::class, $session);

        $mutable_session = $this->receivedRequest()
            ->getAttribute(MutableSession::class);
        $this->assertInstanceOf(MutableSession::class, $mutable_session);
    }

    /**
     * @test
     */
    public function the_session_is_saved(): void
    {
        $m = $this->getMiddleware();

        $this->withNextMiddlewareResponse(function (Response $response, Request $request): Response {
            /** @var MutableSession $session */
            $session = $request->getAttribute(MutableSession::class);
            $session->put('foo', 'bar');
            /** @var ImmutableSession $immutable_session */
            $immutable_session = $request->getAttribute(ImmutableSession::class);

            return $response->withAddedHeader('s-id', $immutable_session->id()->asString());
        });

        $response = $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));
        $response->assertNextMiddlewareCalled();

        $response = $response->assertableResponse()
            ->getPsrResponse();
        $cookies = $response->cookies();

        $headers = $cookies->toHeaders();
        $this->assertTrue(isset($headers[0]));

        $cookie = new AssertableCookie($headers[0]);
        $this->assertSame('test_cookie', $cookie->name);
        $this->assertSame($this->default_config['same_site'], $cookie->same_site);
        $this->assertSame($this->default_config['secure'], $cookie->secure);
        $this->assertSame($this->default_config['http_only'], $cookie->http_only);
        // We did not set an expires timestamp.
        $this->assertSame('', $cookie->expires);
        $this->assertSame(urlencode($response->getHeaderLine('s-id')), $cookie->value);

        $request_with_cookie = $this->frontendRequest()
            ->withCookieParams([
                'test_cookie' => urldecode($cookie->value),
            ]);

        $this->withNextMiddlewareResponse(function (Response $response, Request $request): Response {
            /** @var ImmutableSession $session */
            $session = $request->getAttribute(ImmutableSession::class);

            return $response->withAddedHeader('X-FOO', (string) $session->get('foo'));
        });

        $response = $this->runMiddleware($this->getMiddleware(), $request_with_cookie);
        $response->assertNextMiddlewareCalled();
        $response->assertableResponse()
            ->assertHeader('X-FOO', 'bar');
    }

    /**
     * @test
     */
    public function test_session_cookie_properties(): void
    {
        $this->default_config['absolute_lifetime_in_sec'] = 10;
        $this->default_config['http_only'] = false;
        $this->default_config['secure'] = false;

        $response = $this->runMiddleware($this->getMiddleware(), $this->frontendRequest());

        $response = $response->assertableResponse()
            ->getPsrResponse();
        $cookies = $response->cookies();

        $headers = $cookies->toHeaders();
        $this->assertTrue(isset($headers[0]));

        $cookie = new AssertableCookie($headers[0]);
        $this->assertFalse($cookie->http_only);
        $this->assertFalse($cookie->secure);
        $this->assertSame(gmdate('D, d-M-Y H:i:s', time() + 10) . ' GMT', $cookie->expires);
    }

    /**
     * @test
     */
    public function test_gc_collection_works(): void
    {
        $clock = new TestClock();
        $this->session_driver = new InMemoryDriver($clock);

        $m = $this->getMiddleware();
        $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));
        $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));
        $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));

        $this->assertCount(3, $this->session_driver->all());

        $this->default_config['garbage_collection_percentage'] = 100;

        $clock->travelIntoFuture($this->default_config['idle_timeout_in_sec'] + 1);

        $m = $this->getMiddleware('/', $clock);
        $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));

        // The new id got stored.
        $this->assertCount(1, $this->session_driver->all());
    }

    /**
     * @test
     */
    public function a_failure_in_garbage_collection_will_be_logged_but_not_fail_the_request(): void
    {
        $this->default_config['garbage_collection_percentage'] = 100;
        $this->session_driver = new InMemoryDriver(null, true);

        $m = $this->getMiddleware();
        $response = $this->runMiddleware($m, $this->frontendRequest('/foo', [], 'POST'));
        $response->assertNextMiddlewareCalled();

        $this->logger->hasError([
            'message' => 'Garbage collection failed.',
        ]);
    }

    /**
     * @test
     */
    public function read_verbs_can_be_given_access_to_a_session(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware(
            $m,
            $this->frontendRequest()
                ->withAttribute(StatefulRequest::ALLOW_WRITE_SESSION_FOR_READ_VERBS, true)
        );

        $session = $this->receivedRequest()
            ->getAttribute(MutableSession::class);
        $this->assertInstanceOf(MutableSession::class, $session);
    }

    /**
     * @test
     */
    public function if_the_session_has_no_user_id_the_current_user_id_is_set(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware($m, $this->frontendRequest()->withUserId(1));

        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $session);
        $this->assertSame(1, $session->userId());
    }

    /**
     * @test
     */
    public function a_user_id_is_not_set_if_the_session_already_has_a_user_id(): void
    {
        $manager = new SessionManger(
            SessionConfig::fromDefaults('test_cookie'),
            $this->session_driver,
            new JsonSerializer()
        );
        $session = $manager->start(new CookiePool([]));
        $session->put('foo', 'bar');
        $session->setUserId(12);

        $manager->save($session);

        $request = $this->frontendRequest()
            ->withCookieParams([
                'test_cookie' => $session->id()
                    ->asString(),
            ]);
        $this->runMiddleware($this->getMiddleware(), $request->withUserId(1));

        /** @var ImmutableSession $session */
        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertSame(12, $session->userId(), 'Session user id should have priority over request user id.');
    }

    /**
     * @test
     */
    public function a_user_id_is_not_set_if_the_request_has_no_user_id(): void
    {
        $m = $this->getMiddleware();

        $this->runMiddleware($m, $this->frontendRequest()->withUserId(0));

        $session = $this->receivedRequest()
            ->getAttribute(ImmutableSession::class);
        $this->assertInstanceOf(ImmutableSession::class, $session);
        $this->assertNull($session->userId());
    }

    private function getMiddleware(string $cookie_path = '/', Clock $clock = null): StatefulRequest
    {
        $config = array_merge($this->default_config, ['path', $cookie_path]);

        $manager = new SessionManger(
            new SessionConfig($config),
            $this->session_driver,
            new JsonSerializer(),
            $clock
        );

        return new StatefulRequest($manager, $this->logger, $cookie_path);
    }
}
