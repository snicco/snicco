<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests\unit;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use Snicco\Bundle\HttpRouting\ErrorHandler\DisplayerCollection;
use Snicco\Bundle\HttpRouting\ErrorHandler\ExceptionTransformerCollection;
use Snicco\Bundle\HttpRouting\ErrorHandler\RequestLogContextCollection;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;
use Snicco\Bundle\HttpRouting\RoutingOption;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RouteRunner;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenu;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\Routing;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 */
final class HttpRoutingBundleTest extends TestCase
{

    use BootsKernelForBundleTest;

    private string $base_dir;
    private Directories $directories;

    /**
     * @var array<'testing'|'prod'|'dev'|'staging'|'all', list< class-string<\Snicco\Component\Kernel\Bundle> >>
     */
    private array $bundles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures/tmp';
        $this->directories = $this->setUpDirectories($this->base_dir);
        $this->bundles = [
            Environment::ALL => [
                HttpRoutingBundle::class,
            ]
        ];
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories($this->base_dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = $this->bootWithFixedConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ], $this->directories);

        $this->assertSame(true, $kernel->usesBundle('sniccowp/http-routing-bundle'));
    }

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = $this->bootWithFixedConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ], $this->directories, Environment::testing());
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = $this->bootWithFixedConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ], $this->directories, Environment::prod());
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = $this->bootWithFixedConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ], $this->directories, Environment::dev());
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));

        $kernel = $this->bootWithFixedConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ], $this->directories, Environment::staging());
        $this->assertTrue($kernel->usesBundle('sniccowp/http-routing-bundle'));
    }

    /**
     * @test
     */
    public function test_exception_if_routing_host_is_not_set(): void
    {
        $bundle = new HttpRoutingBundle();
        $config = new WritableConfig([
            'routing' => [
            ]
        ]);

        $kernel = new Kernel($this->container(), Environment::testing(), $this->directories);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('routing.host must be a non-empty-string');

        $bundle->configure($config, $kernel);
    }

    /**
     * @test
     */
    public function test_routing_wp_admin_prefix_defaults_to_wp_admin(): void
    {
        $bundle = new HttpRoutingBundle();
        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ]);

        $kernel = new Kernel($this->container(), Environment::testing(), $this->directories);
        $bundle->configure($config, $kernel);
        $this->assertSame('/wp-admin', $config->get('routing.wp_admin_prefix'));

        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com',
                RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin'
            ]
        ]);
        $bundle->configure($config, $kernel);
        $this->assertSame('/wp/wp-admin', $config->get('routing.wp_admin_prefix'));
    }

    /**
     * @test
     */
    public function test_routing_wp_login_path_has_a_default_set(): void
    {
        $bundle = new HttpRoutingBundle();
        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ]);

        $kernel = new Kernel($this->container(), Environment::testing(), $this->directories);
        $bundle->configure($config, $kernel);
        $this->assertSame('/wp-login.php', $config->get('routing.wp_login_path'));

        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com',
                RoutingOption::WP_LOGIN_PATH => '/wp/wp-login.php'
            ]
        ]);
        $bundle->configure($config, $kernel);
        $this->assertSame('/wp/wp-login.php', $config->get('routing.wp_login_path'));
    }

    /**
     * @test
     */
    public function test_routing_dirs_default_to_empty_array(): void
    {
        $bundle = new HttpRoutingBundle();
        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ]);

        $kernel = new Kernel($this->container(), Environment::testing(), $this->directories);
        $bundle->configure($config, $kernel);
        $this->assertSame([], $config->get('routing.route_directories'));
        $this->assertSame([], $config->get('routing.api_route_directories'));

        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com',
                RoutingOption::ROUTE_DIRECTORIES => [__DIR__],
                RoutingOption::API_ROUTE_DIRECTORIES => [__DIR__ . '/fixtures'],
            ]
        ]);
        $bundle->configure($config, $kernel);
        $this->assertSame([__DIR__], $config->get('routing.route_directories'));
        $this->assertSame([__DIR__ . '/fixtures'], $config->get('routing.api_route_directories'));
    }

    /**
     * @test
     */
    public function test_middleware_options_have_defaults(): void
    {
        $bundle = new HttpRoutingBundle();
        $config = new WritableConfig([
            'routing' => [
                RoutingOption::HOST => 'foo.com'
            ]
        ]);

        $kernel = new Kernel($this->container(), Environment::testing(), $this->directories);
        $bundle->configure($config, $kernel);

        $this->assertSame([], $config->getArray('routing.middleware_groups'));
        $this->assertSame([], $config->getArray('routing.always_run_middleware_groups'));
        $this->assertSame([], $config->getArray('routing.middleware_priority'));
        $this->assertSame([], $config->getArray('routing.middleware_aliases'));
    }

    /**
     * @test
     */
    public function test_urlGenerator_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);
        $this->assertCanBeResolved(UrlGenerator::class, $kernel);
    }

    /**
     * @test
     */
    public function test_urlMatcher_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(UrlMatcher::class, $kernel);

        /** @var UrlMatcher $matcher */
        $matcher = $kernel->container()->make(UrlMatcher::class);

        $result = $matcher->dispatch(Request::fromPsr(new ServerRequest('GET', '/foo')));
        $this->assertFalse($result->isMatch());
    }

    /**
     * @test
     */
    public function test_adminMenu_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(AdminMenu::class, $kernel);
    }

    /**
     * @test
     */
    public function test_routes_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(Routes::class, $kernel);
    }

    /**
     * @test
     */
    public function test_pipeline_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test'
                ]
            ]
            , $this->directories);

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
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::EXCEPTION_TRANSFORMERS => [],
                    RoutingOption::EXCEPTION_DISPLAYERS => [],
                    RoutingOption::EXCEPTION_REQUEST_CONTEXT => [],
                    RoutingOption::EXCEPTION_LOG_LEVELS => [],
                ]
            ],
            $this->directories
        );

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
        $this->assertStringContainsString('<h1>Internal Server Error</h1>', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function the_routing_middleware_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(RoutingMiddleware::class, $kernel);
    }

    /**
     * @test
     */
    public function the_route_runner_middleware_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::ALWAYS_RUN_MIDDLEWARE_GROUPS => [],
                    RoutingOption::MIDDLEWARE_PRIORITY => [],
                    RoutingOption::MIDDLEWARE_ALIASES => [],
                    RoutingOption::MIDDLEWARE_GROUPS => []
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(RouteRunner::class, $kernel);
    }

    /**
     * @test
     */
    public function test_routing_can_be_resolved_in_production_mode(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories, Environment::prod());

        $this->assertCanBeResolved(Routing::class, $kernel);
    }

    /**
     * @test
     */
    public function test_response_factory_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(ResponseFactory::class, $kernel);
        $this->assertCanBeResolved(ResponseFactoryInterface::class, $kernel);
        $this->assertCanBeResolved(StreamFactoryInterface::class, $kernel);
    }

    /**
     * @test
     */
    public function test_server_request__creator_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => true,
                    RoutingOption::HTTP_PORT => 80,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(ServerRequestCreator::class, $kernel);
    }

    /**
     * @test
     */
    public function test_url_generation_context_is_taken_from_config(): void
    {
        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test',
                    RoutingOption::HTTPS => false,
                    RoutingOption::HTTP_PORT => 8080,
                    RoutingOption::HTTPS_PORT => 443,
                ]
            ]
            , $this->directories);

        /** @var UrlGenerator $url_generator */
        $url_generator = $kernel->container()->make(UrlGenerator::class);

        $this->assertSame('http://foo.com:8080/baz', $url_generator->to('/baz', [], UrlGenerator::ABSOLUTE_URL));
    }

    /**
     * @test
     */
    public function test_a_custom_psr17_discovery_can_be_used(): void
    {
        $this->container = $this->container();

        // forces guzzle
        $this->container->instance(
            Psr17FactoryDiscovery::class,
            new Psr17FactoryDiscovery([
                HttpFactory::class => [
                    'server_request' => HttpFactory::class,
                    'uri' => HttpFactory::class,
                    'uploaded_file' => HttpFactory::class,
                    'stream' => HttpFactory::class,
                    'response' => HttpFactory::class,
                ]
            ])
        );

        $kernel = $this->bootWithFixedConfig(
            [
                'routing' => [
                    RoutingOption::HOST => 'foo.com',
                    RoutingOption::ROUTE_DIRECTORIES => [],
                    RoutingOption::API_ROUTE_DIRECTORIES => [],
                    RoutingOption::WP_ADMIN_PREFIX => '/wp/wp-admin',
                    RoutingOption::WP_LOGIN_PATH => '/wp/wp-login',
                    RoutingOption::API_PREFIX => '/test'
                ]
            ]
            , $this->directories);

        $this->assertCanBeResolved(ResponseFactory::class, $kernel);
        $this->assertCanBeResolved(ResponseFactoryInterface::class, $kernel);
        $this->assertCanBeResolved(StreamFactoryInterface::class, $kernel);
    }

    protected function bundles(): array
    {
        return $this->bundles;
    }

}