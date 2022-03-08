<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;

use Closure;
use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;

use WP_UnitTest_Factory_For_User;

use function array_merge;

abstract class WebTestCase extends WPTestCase
{

    use CreateWordPressUsers;

    protected Environment $env;
    private ?Kernel $kernel = null;
    private ?Browser $browser = null;

    /**
     * @var array<string,string>
     */
    private array $server = [];

    /**
     * @var array<string,string>
     */
    private array $cookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->env = Environment::testing();
    }

    /**
     * @return Closure(Environment):Kernel
     */
    abstract protected function createKernel(): Closure;

    /**
     * @param array<string,string> $server
     */
    protected function withServerVariables(array $server): void
    {
        $this->server = array_merge($this->server, $server);
    }

    /**
     * @param array<string,string> $cookies
     */
    protected function withCookies(array $cookies): void
    {
        $this->cookies = array_merge($this->cookies, $cookies);
    }

    protected function getKernel(Environment $env = null): Kernel
    {
        if (!isset($this->kernel)) {
            $env = $env ?: $this->env;
            $this->kernel = ($this->createKernel())($env);
        }

        return $this->kernel;
    }

    protected function swapInstance(string $id, object $instance): void
    {
        $kernel = $this->getKernel();
        $kernel->afterRegister(function (Kernel $kernel) use ($id, $instance) {
            $kernel->container()->instance($id, $instance);
        });
    }

    protected function getBrowser(): Browser
    {
        if (!isset($this->browser)) {
            $this->browser = $this->createBrowser();
        }
        return $this->browser;
    }

    protected function userFactory(): WP_UnitTest_Factory_For_User
    {
        /** @psalm-suppress MixedPropertyFetch */
        /** @psalm-suppress MixedReturnStatement */
        return $this->factory()->user;
    }

    private function createBrowser(): Browser
    {
        $kernel = $this->getKernel();
        $kernel->boot();

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

}