<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Functional;

use Closure;
use Codeception\TestCase\WPTestCase;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Bundle\Session\SessionBundle;
use Snicco\Bundle\Testing\Functional\Concerns\AuthenticateWithWordPress;
use Snicco\Bundle\Testing\Functional\Concerns\CreateWordPressUsers;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\CookiePool;
use Snicco\Component\Session\ValueObject\SessionConfig;
use Snicco\Component\Session\ValueObject\SessionId;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Throwable;
use Webmozart\Assert\Assert;
use WP_UnitTest_Factory_For_User;

use function array_map;
use function array_merge;

abstract class WebTestCase extends WPTestCase
{

    use CreateWordPressUsers;
    use AuthenticateWithWordPress;

    private ?Kernel $kernel = null;
    private ?Browser $browser = null;

    /**
     * @var TestExtension[]
     */
    private array $extensions;

    /**
     * @var array<string,mixed>
     */
    private array $server = [];

    /**
     * @var array<string,string>
     */
    private array $cookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->extensions = array_map(function (string $class) {
            return new $class;
        }, $this->extensions());
        $this->setUpExtensions();
    }

    protected function tearDown(): void
    {
        $this->tearDownExtensions();
        parent::tearDown();
    }

    /**
     * @return Closure(Environment):Kernel
     */
    abstract protected function createKernel(): Closure;

    /**
     * @return class-string<TestExtension>[]
     */
    abstract protected function extensions(): array;

    /**
     * @param array<string,mixed> $server
     */
    final protected function withServerVariables(array $server): void
    {
        $this->assertBrowserNotCreated(__METHOD__);
        $this->server = array_merge($this->server, $server);
    }

    /**
     * @param array<string,string> $cookies
     */
    final protected function withCookies(array $cookies): void
    {
        $this->assertBrowserNotCreated(__METHOD__);
        $this->cookies = array_merge($this->cookies, $cookies);
    }

    /**
     * @param array<string,mixed> $data
     */
    final protected function withDataInSession(array $data): SessionId
    {
        $this->assertBrowserNotCreated(__METHOD__);

        $kernel = $this->getBootedKernel();

        if (!$kernel->usesBundle(SessionBundle::ALIAS)) {
            throw new LogicException('You are not using the session-bundle in your bundles.php config.');
        }

        $cookie_name = $kernel->container()->make(SessionConfig::class)->cookieName();

        $session_manager = $kernel->container()->make(SessionManager::class);
        $session = $session_manager->start(new CookiePool($this->cookies));
        $session->put($data);
        $session_manager->save($session);

        $this->withCookies([$cookie_name => $session->id()->asString()]);

        return $session->id();
    }

    /**
     * @param class-string<MiddlewareInterface>[] $middleware
     */
    final protected function withoutMiddleware(array $middleware): void
    {
        $kernel = $this->getNonBootedKernel(__METHOD__);

        foreach ($middleware as $class) {
            $kernel->afterRegister(function (Kernel $kernel) use ($class) {
                $kernel->container()->instance(
                    $class,
                    new class extends Middleware {

                        protected function handle(Request $request, NextMiddleware $next): ResponseInterface
                        {
                            return $next($request);
                        }
                    }
                );
            });
        }
    }

    final protected function withoutExceptionHandling(): void
    {
        $kernel = $this->getNonBootedKernel(__METHOD__);
        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->instance(
                HttpErrorHandler::class,
                new class implements HttpErrorHandler {

                    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
                    {
                        throw $e;
                    }
                }
            );
        });
    }

    final protected function getEventDispatcher(): TestableEventDispatcher
    {
        return $this->getBootedKernel()->container()->make(TestableEventDispatcher::class);
    }

    final protected function getMailTransport(): FakeTransport
    {
        $transport = $this->getBootedKernel()->container()->make(Transport::class);
        Assert::isInstanceOf($transport, FakeTransport::class);
        return $transport;
    }

    final  protected function getKernel(): Kernel
    {
        if (!isset($this->kernel)) {
            $this->kernel = ($this->createKernel())(Environment::testing());
        }

        return $this->kernel;
    }

    final  protected function swapInstance(string $id, object $instance): void
    {
        $kernel = $this->getNonBootedKernel(__METHOD__);
        $kernel->afterRegister(function (Kernel $kernel) use ($id, $instance) {
            $kernel->container()->instance($id, $instance);
        });
    }

    final protected function getBrowser(): Browser
    {
        if (!isset($this->browser)) {
            $this->browser = $this->createBrowser();
        }
        return $this->browser;
    }

    final protected function userFactory(): WP_UnitTest_Factory_For_User
    {
        /** @psalm-suppress MixedPropertyFetch */
        /** @psalm-suppress MixedReturnStatement */
        return $this->factory()->user;
    }

    final protected function getBootedKernel(): Kernel
    {
        $kernel = $this->getKernel();
        if (!$kernel->booted()) {
            $kernel->boot();
        }
        return $kernel;
    }

    private function createBrowser(): Browser
    {
        $kernel = $this->getBootedKernel();

        $cookies = new CookieJar();

        foreach ($this->cookies as $cookie => $value) {
            $cookies->set(new Cookie($cookie, $value));
        }

        $this->withServerVariables([
            'HTTP_HOST' => $kernel->config()->getString('routing.' . RoutingOption::HOST),
            'HTTPS' => $kernel->config()->getBoolean('routing.' . RoutingOption::USE_HTTPS)
        ]);

        return new Browser(
            $kernel->container()->make(HttpKernel::class),
            $kernel->container()->make(Psr17FactoryDiscovery::class),
            AdminAreaPrefix::fromString($kernel->config()->getString('routing.' . RoutingOption::WP_ADMIN_PREFIX)),
            UrlPath::fromString($kernel->config()->getString('routing.' . RoutingOption::API_PREFIX)),
            $this->server,
            null,
            $cookies
        );
    }

    private function setUpExtensions(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->setUp();
        }
    }

    private function tearDownExtensions(): void
    {
        foreach ($this->extensions as $extension) {
            $extension->tearDown();
        }
    }

    private function getNonBootedKernel(string $__METHOD__): Kernel
    {
        $kernel = $this->getKernel();
        if ($kernel->booted()) {
            throw new LogicException("Method [$__METHOD__] can not be used if the kernel was already booted.");
        }
        return $kernel;
    }

    private function assertBrowserNotCreated(string $__METHOD__): void
    {
        if (isset($this->browser)) {
            throw new LogicException("Method [$__METHOD__] can not be used if the browser was already created.");
        }
    }

}