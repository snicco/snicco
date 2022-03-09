<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use GuzzleHttp\Psr7\HttpFactory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Middleware\ErrorsToExceptions;
use Snicco\Bundle\HttpRouting\Middleware\SetUserId;
use Snicco\Bundle\HttpRouting\Option\RoutingOption;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class HttpRoutingBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_exception_if_better_wp_hooks_bundle_is_not_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [HttpRoutingBundle::class],
            ]);
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The http-routing-bundle needs the sniccowp-better-wp-hooks-bundle to run.');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertSame(true, $kernel->usesBundle('sniccowp/http-routing-bundle'));
    }

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(false),
            $this->directories
        );
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::staging(),
            $this->directories
        );
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));
    }

    /**
     * @test
     */
    public function test_routing_wp_admin_prefix_defaults_to_wp_admin(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertSame('/wp-admin', $kernel->config()->getString('routing.wp_admin_prefix'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::WP_ADMIN_PREFIX, '/wp/wp-admin');
        });

        $kernel->boot();

        $this->assertSame('/wp/wp-admin', $kernel->config()->getString('routing.wp_admin_prefix'));
    }

    /**
     * @test
     */
    public function test_routing_wp_login_path_has_a_default_set(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertSame('/wp-login.php', $kernel->config()->getString('routing.wp_login_path'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.' . RoutingOption::WP_LOGIN_PATH, '/wp/wp-login.php');
        });

        $kernel->boot();

        $this->assertSame('/wp/wp-login.php', $kernel->config()->getString('routing.wp_login_path'));
    }

    /**
     * @test
     */
    public function test_routing_dirs_default_to_empty_array(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing', [
                RoutingOption::HOST => 'sniccowp.test',
            ]);
        });

        $kernel->boot();

        $this->assertSame([], $kernel->config()->getListOfStrings('routing.route_directories'));
        $this->assertSame([], $kernel->config()->getListOfStrings('routing.api_route_directories'));

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing.route_directories', [
                dirname(__DIR__) . '/fixtures/routes',
            ]);
            $config->set('routing.api_route_directories', [
                dirname(__DIR__) . '/fixtures/routes/api',
            ]);
            $config->set('routing.api_prefix', 'snicco');
        });
        $kernel->boot();

        $this->assertSame([dirname(__DIR__) . '/fixtures/routes'], $kernel->config()->get('routing.route_directories'));
        $this->assertSame(
            [dirname(__DIR__) . '/fixtures/routes/api'],
            $kernel->config()->get('routing.api_route_directories')
        );
    }

    /**
     * @test
     */
    public function test_middleware_options_have_defaults(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('middleware', []);
        });
        $kernel->boot();

        $this->assertSame([
            'frontend' => [],
            'admin' => [],
            'api' => [],
            'global' => [
                SetUserId::class,
            ],
        ], $kernel->config()->getArray('middleware.middleware_groups'));
        $this->assertSame([], $kernel->config()->getArray('middleware.always_run_middleware_groups'));
        $this->assertSame([], $kernel->config()->getArray('middleware.middleware_priority'));
        $this->assertSame([], $kernel->config()->getArray('middleware.middleware_aliases'));
    }

    /**
     * @test
     */
    public function test_kernel_middleware_defaults_to_the_correct_routing_setup(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertSame(
            [ErrorsToExceptions::class, RoutingMiddleware::class, RouteRunner::class],
            $kernel->config()->getArray('middleware.kernel_middleware')
        );
    }

    /**
     * @test
     */
    public function test_urlGenerator_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(UrlGenerator::class, $kernel);
    }

    /**
     * @test
     */
    public function test_urlMatcher_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(UrlMatcher::class, $kernel);

        /** @var UrlMatcher $matcher */
        $matcher = $kernel->container()->make(UrlMatcher::class);

        $result = $matcher->dispatch(Request::fromPsr(new ServerRequest('GET', '/frontend')));
        $this->assertTrue($result->isMatch());
    }

    /**
     * @test
     */
    public function test_adminMenu_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(AdminMenu::class, $kernel);
    }

    /**
     * @test
     */
    public function test_routes_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(Routes::class, $kernel);
    }

    /**
     * @test
     */
    public function test_pipeline_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(MiddlewarePipeline::class, $kernel);
        $p1 = $kernel->container()->make(MiddlewarePipeline::class);
        $p2 = $kernel->container()->make(MiddlewarePipeline::class);
        $this->assertNotSame($p1, $p2);
    }

    /**
     * @test
     */
    public function test_pipeline_error_handler_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        /** @var MiddlewarePipeline $p1 */
        $p1 = $kernel->container()->make(MiddlewarePipeline::class);

        $psr_request = new ServerRequest('GET', '/foo');

        $response = $p1
            ->send(Request::fromPsr($psr_request))
            ->through([])
            ->then(function () {
                throw new RuntimeException('error');
            });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('<h1>500 - Internal Server Error</h1>', (string) $response->getBody());
    }

    /**
     * @test
     */
    public function the_routing_middleware_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(RoutingMiddleware::class, $kernel);
    }

    /**
     * @test
     */
    public function the_route_runner_middleware_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(RouteRunner::class, $kernel);
    }

    /**
     * @test
     */
    public function test_routing_can_be_resolved_in_production_mode(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(Router::class, $kernel);
    }

    /**
     * @test
     */
    public function test_response_factory_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(ResponseFactory::class, $kernel);
        $this->assertCanBeResolved(ResponseFactoryInterface::class, $kernel);
        $this->assertCanBeResolved(StreamFactoryInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_server_request__creator_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->boot();

        $this->assertCanBeResolved(ServerRequestCreator::class, $kernel);
    }

    /**
     * @test
     */
    public function test_url_generation_context_is_taken_from_config(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('routing', [
                RoutingOption::HOST => 'foo.com',
                RoutingOption::ROUTE_DIRECTORIES => [],
                RoutingOption::API_ROUTE_DIRECTORIES => [],
                RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                RoutingOption::API_PREFIX => '/test',
                RoutingOption::USE_HTTPS => false,
                RoutingOption::HTTP_PORT => 8080,
                RoutingOption::HTTPS_PORT => 443,
            ]);
        });

        $kernel->boot();

        /** @var UrlGenerator $url_generator */
        $url_generator = $kernel->container()->make(UrlGenerator::class);

        $this->assertSame('http://foo.com:8080/baz', $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL));
    }

    /**
     * @test
     */
    public function test_a_custom_psr17_discovery_can_be_used(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->instance(
                Psr17FactoryDiscovery::class,
                new Psr17FactoryDiscovery([
                    HttpFactory::class => [
                        'server_request' => HttpFactory::class,
                        'uri' => HttpFactory::class,
                        'uploaded_file' => HttpFactory::class,
                        'stream' => HttpFactory::class,
                        'response' => HttpFactory::class,
                    ],
                ])
            );
        });

        $kernel->boot();

        $this->assertCanBeResolved(ResponseFactory::class, $kernel);
        $this->assertCanBeResolved(ResponseFactoryInterface::class, $kernel);
        $this->assertCanBeResolved(StreamFactoryInterface::class, $kernel);

        /**
         * @var Psr17FactoryDiscovery $discovery
         */
        $discovery = $kernel->container()->make(Psr17FactoryDiscovery::class);
        $this->assertInstanceOf(HttpFactory::class, $discovery->createResponseFactory());
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}
