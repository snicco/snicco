<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Nyholm\Psr7\ServerRequest;
use RuntimeException;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Controller\HttpRunnerTestController;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function add_filter;
use function dirname;

/**
 * @internal
 *
 * @psalm-internal Snicco
 *
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class RoutingContextFromRuntimeValuesTest extends WPTestCase
{
    use BundleTestHelpers;

    private Kernel $kernel;

    /**
     * @test
     */
    public function test_url_generation_context_can_be_parsed_entirely_from_runtime_with_https_as_scheme(): void
    {
        add_filter('home_url', function () {
            return 'https://snicco.io:8443';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => null,
                RoutingOption::USE_HTTPS => null,
                RoutingOption::HTTP_PORT => null,
                RoutingOption::HTTPS_PORT => null,
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::API_PREFIX => '/test',
            ]);
        });

        $kernel->boot();

        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame(
            'https://snicco.io:8443/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL)
        );
        $this->assertSame(
            'http://snicco.io/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, false)
        );
    }

    /**
     * @test
     */
    public function test_url_generation_context_can_be_parsed_entirely_from_runtime_with_https_as_scheme_and_explicit_http_port(): void
    {
        add_filter('home_url', function () {
            return 'https://snicco.io:8443';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => null,
                RoutingOption::USE_HTTPS => null,
                RoutingOption::HTTP_PORT => 8080,
                RoutingOption::HTTPS_PORT => null,
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::API_PREFIX => '/test',
            ]);
        });

        $kernel->boot();

        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame(
            'https://snicco.io:8443/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL)
        );
        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, false)
        );
    }

    /**
     * @test
     */
    public function test_url_generation_context_can_be_parsed_entirely_from_runtime_with_http_as_scheme(): void
    {
        add_filter('home_url', function () {
            return 'http://snicco.io:8080';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => null,
                RoutingOption::USE_HTTPS => null,
                RoutingOption::HTTP_PORT => null,
                RoutingOption::HTTPS_PORT => null,
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::API_PREFIX => '/test',
            ]);
        });

        $kernel->boot();

        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL)
        );
        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, false)
        );
        $this->assertSame(
            'https://snicco.io/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, true)
        );
    }

    /**
     * @test
     */
    public function test_url_generation_context_can_be_parsed_entirely_from_runtime_with_http_as_scheme_and_https_port_specific(): void
    {
        add_filter('home_url', function () {
            return 'http://snicco.io:8080';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => null,
                RoutingOption::USE_HTTPS => null,
                RoutingOption::HTTP_PORT => null,
                RoutingOption::HTTPS_PORT => 8443,
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::API_PREFIX => '/test',
            ]);
        });

        $kernel->boot();

        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL)
        );
        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, false)
        );
        $this->assertSame(
            'https://snicco.io:8443/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, true)
        );
    }

    /**
     * @test
     */
    public function test_url_generation_context_can_be_parsed_partially_from_runtime(): void
    {
        add_filter('home_url', function () {
            return 'http://snicco.io:1043';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing', [
                RoutingOption::HOST => null,
                RoutingOption::USE_HTTPS => true,
                RoutingOption::HTTP_PORT => 8080,
                RoutingOption::HTTPS_PORT => 8443,
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::API_PREFIX => '/test',
            ]);
        });

        $kernel->boot();

        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame(
            'https://snicco.io:8443/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL)
        );
        $this->assertSame(
            'http://snicco.io:8080/baz',
            $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL, false)
        );
    }

    /**
     * @test
     */
    public function the_login_url_can_be_entirely_parsed_from_the_runtime(): void
    {
        add_filter('login_url', function () {
            return 'https://snicco.io/login';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.host', 'snicco.io');
            $config->set('routing.wp_login_path', null);
        });

        $kernel->boot();

        /** @var UrlGenerator $url_generator */
        $url_generator = $kernel->container()
            ->make(UrlGenerator::class);

        $this->assertSame('/login', $url_generator->toLogin());

        add_filter('login_url', function () {
            return 'https://snicco.io/login-2';
        });

        $this->assertSame('/login-2', $url_generator->toLogin());
    }

    /**
     * @test
     */
    public function the_admin_url_can_be_entirely_parsed_from_the_runtime_with_custom_admin_url(): void
    {
        add_filter('admin_url', function () {
            return 'https://snicco.io/my-custom-admin';
        });

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.host', 'snicco.io');
            $config->set('routing.' . RoutingOption::WP_ADMIN_PREFIX, null);
        });

        $kernel->boot();

        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/my-custom-admin/admin.php?page=foo');

        $response = $pipeline
            ->send(Request::fromPsr($request, Request::TYPE_ADMIN_AREA))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(HttpRunnerTestController::class, (string) $response->getBody());
    }

    /**
     * @test
     */
    public function the_admin_url_can_be_entirely_parsed_from_the_runtime_and_can_use_default_admin_url(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('routing.host', 'snicco.io');
            $config->set('routing.' . RoutingOption::WP_ADMIN_PREFIX, null);
        });

        $kernel->boot();

        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = new ServerRequest('GET', '/wp-admin/admin.php?page=foo');

        $response = $pipeline
            ->send(Request::fromPsr($request, Request::TYPE_ADMIN_AREA))
            ->through([RoutingMiddleware::class, RouteRunner::class])->then(function (): void {
                throw new RuntimeException('no routing performed');
            });

        $this->assertSame(HttpRunnerTestController::class, (string) $response->getBody());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
