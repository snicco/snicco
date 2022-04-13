<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\Functional;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\HttpRouting\Event\HandlingRequest;
use Snicco\Bundle\Session\SessionBundle;
use Snicco\Bundle\Testing\Functional\TestExtension;
use Snicco\Bundle\Testing\Functional\WebTestCase;
use Snicco\Bundle\Testing\Tests\wordpress\fixtures\MiddlewareThatAlwaysThrowsException;
use Snicco\Bundle\Testing\Tests\wordpress\fixtures\WebTestCaseController;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use stdClass;

use function dirname;

/**
 * @internal
 */
final class WebTestCaseTest extends WebTestCase
{
    /**
     * @var array<class-string<DummyTestExtension>>
     */
    private const EXTENSIONS = [DummyTestExtension::class];

    protected function setUp(): void
    {
        unset($_SERVER[DummyTestExtension::class]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($_SERVER[DummyTestExtension::class]);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function the_kernel_is_not_booted_immediately(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();
        $this->assertTrue($kernel->env()->isTesting());
    }

    /**
     * @test
     */
    public function the_kernel_is_not_created_twice(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $this->assertSame($kernel, $this->getKernel());
    }

    /**
     * @test
     */
    public function test_swap_instance(): void
    {
        $this->swapInstance(stdClass::class, $std = new stdClass());
        $kernel = $this->getKernel();
        $kernel->boot();

        $this->assertSame($std, $kernel->container()->make(stdClass::class));
    }

    /**
     * @test
     */
    public function the_browser_is_configured_correctly(): void
    {
        $browser = $this->getBrowser();

        $crawler = $browser->request('GET', '/foo');
        $this->assertSame(WebTestCaseController::class, $crawler->filter('h1')->first()->innerText());

        $this->assertSame($browser, $this->getBrowser());

        $browser->getResponse()
            ->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function default_server_variables_can_be_added(): void
    {
        $this->withServerVariables([
            'X-FOO' => 'BAR',
        ]);
        $this->withServerVariables([
            'X-BAR' => 'BAZ',
        ]);

        $browser = $this->getBrowser();

        $browser->request('GET', '/custom-server-vars');

        $response = $browser->getResponse();

        $response->assertOk();
        $response->assertSeeText('X-FOO=BAR');
        $response->assertSeeText('X-BAR=BAZ');
    }

    /**
     * @test
     */
    public function default_cookies_can_be_added(): void
    {
        $this->withCookies([
            'foo' => 'bar',
        ]);
        $this->withCookies([
            'bar' => 'baz',
        ]);

        $browser = $this->getBrowser();

        $browser->request('GET', '/cookies-as-json');

        $response = $browser->lastResponse();

        $response->assertOk();

        $body = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
        ], $body);
    }

    /**
     * @test
     */
    public function the_scheme_and_host_are_configured_correctly(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/full-url');

        $response = $browser->lastResponse();

        $response->assertOk()
            ->assertSeeText('https://snicco.test/full-url');
    }

    /**
     * @test
     */
    public function that_the_testable_event_dispatcher_is_registered_automatically(): void
    {
        $kernel = $this->getBootedKernel();
        $event_dispatcher = $kernel->container()
            ->make(EventDispatcher::class);
        $psr_event_dispatcher = $kernel->container()
            ->make(EventDispatcherInterface::class);

        $this->assertSame($event_dispatcher, $psr_event_dispatcher);
        $this->assertInstanceOf(TestableEventDispatcher::class, $event_dispatcher);
    }

    /**
     * @test
     */
    public function test_fake_events(): void
    {
        $event_dispatcher = $this->getEventDispatcher();
        $event_dispatcher->fake([HandlingRequest::class]);

        $event_dispatcher->assertNotDispatched(HandlingRequest::class);

        $browser = $this->getBrowser();

        $browser->request('GET', '/full-url');

        $response = $browser->lastResponse();

        $response->assertOk()
            ->assertSeeText('https://snicco.test/full-url');

        $event_dispatcher->assertDispatched(HandlingRequest::class);
    }

    /**
     * @test
     */
    public function test_fake_mailer(): void
    {
        $browser = $this->getBrowser();

        $mail_transport = $this->getMailTransport();
        $mail_transport->assertNotSent(Email::class);

        $browser->request('POST', '/send-mail', [
            'to' => 'c@web.de',
            'message' => 'Hello Calvin',
        ]);

        $mail_transport->assertSentTo('c@web.de', Email::class);

        $response = $browser->lastResponse();

        $response->assertOk()
            ->assertSeeText('Mail sent!');
    }

    /**
     * @test
     */
    public function test_with_session_data_throws_exception_if_bundle_not_used(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('session-bundle');
        $this->withDataInSession([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function test_with_session_data_works(): void
    {
        $kernel = $this->getKernel();
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->appendToList('kernel.bundles.all', [SessionBundle::class, BetterWPDBBundle::class]);
        });

        $id = $this->withDataInSession([
            'counter' => 0,
        ]);

        $browser = $this->getBrowser();

        $browser->request('POST', '/increment-counter');

        $response = $browser->lastResponse();
        $response->assertOk();

        $info = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        $this->assertEquals([
            'id' => $id->asString(),
            'counter' => 1,
        ], $info);
    }

    /**
     * @test
     */
    public function session_data_is_not_lost_between_requests(): void
    {
        $kernel = $this->getKernel();
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->appendToList('kernel.bundles.all', [SessionBundle::class, BetterWPDBBundle::class]);
        });

        $id = $this->withDataInSession([
            'counter' => 0,
        ]);
        $browser = $this->getBrowser();

        $browser->request('POST', '/increment-counter');
        $browser->request('POST', '/increment-counter');
        $browser->request('POST', '/increment-counter');

        $response = $browser->lastResponse();
        $response->assertOk();

        $info = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

        $this->assertEquals([
            'id' => $id->asString(),
            'counter' => 3,
        ], $info);
    }

    /**
     * @test
     */
    public function test_extensions_are_set_up_and_teared_down(): void
    {
        $this->assertTrue($_SERVER[DummyTestExtension::class]['setUp'] ?? false);
    }

    /**
     * @test
     */
    public function test_without_middleware(): void
    {
        $this->withoutMiddleware([MiddlewareThatAlwaysThrowsException::class]);

        $browser = $this->getBrowser();

        $browser->request('GET', '/force-exception-middleware');

        $response = $browser->lastResponse();

        $response->assertOk();
    }

    /**
     * @test
     */
    public function test_without_exception_handling(): void
    {
        $this->withoutExceptionHandling();

        $browser = $this->getBrowser();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(MiddlewareThatAlwaysThrowsException::class);

        $browser->request('GET', '/force-exception-middleware');
    }

    /**
     * @test
     */
    public function test_exception_if_without_middleware_is_called_after_boot(): void
    {
        $this->getBootedKernel();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('::withoutMiddleware] can not be used if the kernel was already booted');
        $this->withoutMiddleware([MiddlewareThatAlwaysThrowsException::class]);
    }

    /**
     * @test
     */
    public function test_exception_if_without_error_handling_is_called_after_boot(): void
    {
        $this->getBootedKernel();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('::withoutExceptionHandling] can not be used if the kernel was already booted');
        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function test_exception_if_with_cookies_is_called_after_browser_creation(): void
    {
        $this->getBrowser();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('::withCookies] can not be used if the browser was already created.');
        $this->withCookies([]);
    }

    /**
     * @test
     */
    public function test_exception_if_with_server_variables_is_called_after_browser_creation(): void
    {
        $this->getBrowser();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('::withServerVariables] can not be used if the browser was already created.');
        $this->withServerVariables([]);
    }

    /**
     * @test
     */
    public function test_exception_if_with_data_in_session_is_called_after_browser_creation(): void
    {
        $this->getBrowser();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('::withDataInSession] can not be used if the browser was already created.');
        $this->withDataInSession([]);
    }

    /**
     * @test
     */
    public function test_assertable_dom_is_available_after_request(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/foo');

        $last_dom = $browser->lastDOM();

        $last_dom->assertSelectorTextSame('h1', WebTestCaseController::class);
        $last_dom->assertSelectorExists('h1');
        $last_dom->assertSelectorNotExists('h2');
        $last_dom->assertSelectorTextNotContains('h1', 'foo');
    }

    /**
     * @test
     */
    public function test_assertable_dom_throws_exception_if_response_was_delegated(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/bogus');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The response was delegated to WordPress so that no DOM is available.');
        $browser->lastDOM();
    }

    /**
     * @test
     */
    public function test_assertable_dom_throws_exception_before_request(): void
    {
        $browser = $this->getBrowser();
        $this->expectException(LogicException::class);
        $browser->lastDOM();
    }

    protected function extensions(): array
    {
        return self::EXTENSIONS;
    }

    protected function createKernel(): callable
    {
        return require dirname(__DIR__) . '/fixtures/test-kernel.php';
    }
}

final class DummyTestExtension implements TestExtension
{
    public bool $setUp = false;

    public bool $tearDown = false;

    public function setUp(): void
    {
        /** @psalm-suppress MixedArrayAssignment */
        $_SERVER[DummyTestExtension::class]['setUp'] = true;
    }

    public function tearDown(): void
    {
        /** @psalm-suppress MixedArrayAssignment */
        $_SERVER[DummyTestExtension::class]['tearDown'] = true;
    }
}
