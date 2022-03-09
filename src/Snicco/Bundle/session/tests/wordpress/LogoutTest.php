<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Bundle\Session\Option\SessionOption;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\MutableSession;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\CookiePool;

use function array_key_first;
use function dirname;
use function json_decode;
use function ob_end_clean;
use function ob_start;
use function wp_logout;

use const JSON_THROW_ON_ERROR;

final class LogoutTest extends WPTestCase
{
    use BundleTestHelpers;

    private Kernel $kernel;
    private InMemoryDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        unset($_COOKIE['test_cookie']);
        $this->kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $this->kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::COOKIE_NAME => 'test_cookie',
            ]);
            $config->extend('bundles.all', [HttpRoutingBundle::class, BetterWPHooksBundle::class]);
        });
        $this->bundle_test->withoutHttpErrorHandling($this->kernel);
        $this->kernel->boot();
        /**
         * @var InMemoryDriver $driver
         */
        $driver = $this->kernel->container()->make(SessionDriver::class);
        $this->driver = $driver;
    }

    protected function tearDown(): void
    {
        $this->bundle_test->tearDownDirectories();
        unset($_COOKIE['test_cookie']);
        parent::tearDown();
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

    /**
     * @test
     */
    public function a_valid_session_is_invalidated_on_wp_logout(): void
    {
        /**
         * @var SessionManager $m
         */
        $m = $this->kernel->container()->get(SessionManager::class);
        $session = $m->start(CookiePool::fromSuperGlobals());
        $session->put('foo', 'bar');

        // We have a valid session that's saved in the driver
        $m->save($session);
        $this->assertTrue(isset($this->driver->all()[$session->id()->selector()]));

        // And then a request outside the routing flow, which logs out the user.
        $_COOKIE['test_cookie'] = $session->id()->asString();
        ob_start();
        wp_logout();

        $this->assertFalse(isset($this->driver->all()[$session->id()->selector()]));
        ob_end_clean();

        $first_session = array_key_first($this->driver->all());
        $this->assertIsString($first_session, 'No new session generated');

        $serialized_session = $this->driver->read($first_session);
        $data = (array)json_decode($serialized_session->data(), true, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('foo', $data);
    }

    /**
     * @test
     */
    public function calling_wp_logout_during_the_routing_flow_works(): void
    {
        /**
         * @var SessionManager $m
         */
        $m = $this->kernel->container()->get(SessionManager::class);
        $session = $m->start(CookiePool::fromSuperGlobals());
        $session->put('foo', 'bar');
        $m->save($session);

        /**
         * @var MiddlewarePipeline $pipeline
         */
        $pipeline = $this->kernel->container()->get(MiddlewarePipeline::class);

        $_COOKIE['test_cookie'] = $session->id()->asString();
        $request = Request::fromPsr(new ServerRequest('GET', '/'))->withCookieParams([
            'test_cookie' => $session->id()->asString(),
        ]);

        $this->assertTrue(isset($this->driver->all()[$session->id()->selector()]));

        $response = $pipeline->send($request)
            ->through([
                StatefulRequest::class,
            ])
            ->then(function () {
                wp_logout();
                return new Response();
            });

        $this->assertFalse(
            isset($this->driver->all()[$session->id()->selector()]),
            'Session not invalidated on logout.'
        );
        $this->assertSame(200, $response->getStatusCode());

        $first_session = array_key_first($this->driver->all());
        $this->assertIsString($first_session, 'No new session generated');

        $serialized_session = $this->driver->read($first_session);
        $data = (array)json_decode($serialized_session->data(), true, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('foo', $data);
    }

    /**
     * @test
     */
    public function manually_invalidating_a_session_works_in_the_routing_flow(): void
    {
        /**
         * @var SessionManager $m
         */
        $m = $this->kernel->container()->get(SessionManager::class);
        $session = $m->start(CookiePool::fromSuperGlobals());
        $session->put('foo', 'bar');
        $m->save($session);

        /**
         * @var MiddlewarePipeline $pipeline
         */
        $pipeline = $this->kernel->container()->get(MiddlewarePipeline::class);

        $_COOKIE['test_cookie'] = $session->id()->asString();
        $request = Request::fromPsr(new ServerRequest('POST', '/'))->withCookieParams([
            'test_cookie' => $session->id()->asString(),
        ]);

        $this->assertTrue(isset($this->driver->all()[$session->id()->selector()]));

        $response = $pipeline->send($request)
            ->through([
                StatefulRequest::class,
            ])
            ->then(function (Request $request) {
                /**
                 * @var MutableSession $session
                 */
                $session = $request->getAttribute(MutableSession::class);
                $session->invalidate();
                wp_logout();
                return new Response();
            });

        $this->assertFalse(
            isset($this->driver->all()[$session->id()->selector()]),
            'Session not invalidated on logout.'
        );
        $this->assertSame(200, $response->getStatusCode());

        $first_session = array_key_first($this->driver->all());
        $this->assertIsString($first_session, 'No new session generated');

        $serialized_session = $this->driver->read($first_session);
        $data = (array)json_decode($serialized_session->data(), true, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('foo', $data);
    }

    /**
     * @test
     */
    public function no_new_session_is_saved_on_logout_if_the_user_had_no_session(): void
    {
        $this->assertCount(0, $this->driver->all());
        wp_logout();
        $this->assertCount(0, $this->driver->all());
    }
}
