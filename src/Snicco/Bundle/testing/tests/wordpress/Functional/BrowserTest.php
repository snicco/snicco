<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\Functional;

use BadMethodCallException;
use Closure;
use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\HttpRouting\HttpKernel;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Bundle\Testing\Functional\Browser;
use Snicco\Bundle\Testing\Tests\wordpress\fixtures\WebTestCaseController;
use Snicco\Component\HttpRouting\Routing\Admin\AdminAreaPrefix;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\HttpRouting\Testing\AssertableResponse;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\TestErrorHandler;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

use function dirname;
use function filesize;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class BrowserTest extends WPTestCase
{
    /**
     * @var Closure(Environment):Kernel
     */
    private Closure $boot_kernel_closure;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boot_kernel_closure = require dirname(__DIR__) . '/fixtures/test-kernel.php';
    }

    /**
     * @test
     */
    public function test_browser_returns_crawler(): void
    {
        $browser = $this->getBrowser();

        $crawler = $browser->request('GET', '/foo?bar=baz');

        $this->assertInstanceOf(Crawler::class, $crawler);
    }

    /**
     * @test
     */
    public function test_get_request_throws_exception(): void
    {
        $browser = $this->getBrowser();

        $this->expectException(BadMethodCallException::class);

        $browser->request('GET', '/foo');

        $browser->getRequest();
    }

    /**
     * @test
     */
    public function test_get_response_returns_assertable_response(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/bogus-bogus');

        $response = $browser->getResponse();
        $this->assertInstanceOf(AssertableResponse::class, $response);
        $response->assertDelegated();
    }

    /**
     * @test
     */
    public function test_basic_routing_works(): void
    {
        $browser = $this->getBrowser();

        $crawler = $browser->request('GET', '/foo');
        $node = $crawler->filter('h1')
            ->first();
        $this->assertSame(WebTestCaseController::class, $node->innerText());

        $response = $browser->getResponse();
        $response->assertStatus(200);
        $response->assertNotDelegated();
        $response->assertSeeText(WebTestCaseController::class);
    }

    /**
     * @test
     */
    public function test_query_params_are_added_to_the_request(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/query-params-as-json?foo=bar&baz=biz');

        $response = $browser->getResponse();
        $response->assertStatus(200);
        $response->assertNotDelegated()
            ->assertIsJson();

        $body = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
        $this->assertEquals([
            'foo' => 'bar',
            'baz' => 'biz',
        ], $body);
    }

    /**
     * @test
     */
    public function test_request_cookies_are_added(): void
    {
        $foo = new Cookie('cookie1', 'foo');
        $bar = new Cookie('cookie2', 'bar');
        $cookies = new CookieJar();
        $cookies->set($foo);
        $cookies->set($bar);

        $browser = $this->getBrowser([], $cookies);

        $browser->request('GET', '/cookies-as-json');

        $response = $browser->getResponse();
        $response->assertStatus(200);
        $response->assertNotDelegated()
            ->assertIsJson();

        $body = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
        $this->assertEquals([
            'cookie1' => 'foo',
            'cookie2' => 'bar',
        ], $body);
    }

    /**
     * @test
     */
    public function test_body_is_added(): void
    {
        $browser = $this->getBrowser();

        $browser->request('POST', '/body-as-json', [
            'foo' => 'bar',
            'baz' => 'biz',
        ]);

        $response = $browser->getResponse();
        $response->assertStatus(200);
        $response->assertNotDelegated()
            ->assertIsJson();

        $body = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
        $this->assertEquals([
            'foo' => 'bar',
            'baz' => 'biz',
        ], $body);
    }

    /**
     * @test
     */
    public function test_uploaded_files_are_added(): void
    {
        $browser = $this->getBrowser();

        $browser->request('POST', '/files-as-json', [], [
            'php-image-custom-name' => dirname(__DIR__) . '/fixtures/php-image.png',
        ]);

        $response = $browser->getResponse();
        $response->assertStatus(200);
        $response->assertNotDelegated()
            ->assertIsJson();

        $body = (array) json_decode($response->body(), true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
        $this->assertEquals([
            [
                'size' => filesize(dirname(__DIR__) . '/fixtures/php-image.png'),
                'name' => 'php-image-custom-name',
            ],
        ], $body);
    }

    /**
     * @test
     */
    public function test_admin_requests_are_created_if_the_prefix_is_correct(): void
    {
        $browser = $this->getBrowser();
        $browser->request('GET', '/wp-admin/admin.php?page=foo');

        $browser->getResponse()
            ->assertSeeText('admin')
            ->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function test_api_requests_are_created_correctly(): void
    {
        $browser = $this->getBrowser();
        $browser->request('GET', '/api/test/check-api');

        $browser->getResponse()
            ->assertSeeText('true')
            ->assertOk()
            ->assertNotDelegated();

        $browser->request('GET', '/check-api');

        $browser->getResponse()
            ->assertSeeText('false')
            ->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function test_assertable_dom(): void
    {
        $browser = $this->getBrowser();

        $browser->request('GET', '/foo');

        $last_dom = $browser->lastDOM();

        $last_dom->assertSelectorTextSame('h1', WebTestCaseController::class);
    }

    /**
     * @param array<string,mixed> $server
     */
    private function getBrowser(array $server = [], CookieJar $cookies = null): Browser
    {
        $kernel = ($this->boot_kernel_closure)(Environment::testing());
        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->instance(HttpErrorHandler::class, new TestErrorHandler());
        });
        $kernel->boot();

        return new Browser(
            $kernel->container()
                ->make(HttpKernel::class),
            $kernel->container()
                ->make(Psr17FactoryDiscovery::class),
            AdminAreaPrefix::fromString('/wp-admin'),
            UrlPath::fromString('/api'),
            $server,
            null,
            $cookies
        );
    }
}
