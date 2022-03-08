<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing\Tests\wordpress;

use Closure;
use Snicco\Bundle\Testing\Tests\fixtures\WebTestCaseController;
use Snicco\Bundle\Testing\WebTestCase;
use Snicco\Component\Kernel\ValueObject\Environment;
use stdClass;

use function dirname;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class WebTestCaseTest extends WebTestCase
{
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
        $kernel = $this->getKernel(Environment::dev());
        $kernel->boot();
        $this->assertTrue($kernel->env()->isDevelop());

        $this->assertSame($kernel, $this->getKernel());
    }

    /**
     * @test
     */
    public function test_swapInstance(): void
    {
        $this->swapInstance(stdClass::class, $std = new stdClass());
        $kernel = $this->getKernel(Environment::dev());
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

        $browser->getResponse()->assertOk()->assertNotDelegated();
    }

    /**
     * @test
     */
    public function default_server_variables_can_be_added(): void
    {
        $this->withServerVariables(['X-FOO' => 'BAR']);
        $this->withServerVariables(['X-BAR' => 'BAZ']);

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
        $this->withCookies(['foo' => 'bar']);
        $this->withCookies(['bar' => 'baz']);

        $browser = $this->getBrowser();

        $browser->request('GET', '/cookies-as-json');
        $response = $browser->lastResponse();

        $response->assertOk();

        $body = (array)json_decode($response->body(), true, JSON_THROW_ON_ERROR);

        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz'
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

        $response->assertOk()->assertSeeText('https://sniccowp.test/full-url');
    }

    protected function createKernel(): Closure
    {
        return require dirname(__DIR__) . '/fixtures/test-kernel.php';
    }
}