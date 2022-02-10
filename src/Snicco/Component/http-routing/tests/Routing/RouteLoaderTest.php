<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\RouteLoading\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoading\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoading\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function dirname;

final class RouteLoaderTest extends HttpRunnerTestCase
{

    const WEB_PATH = '/web';
    const PARTIAL_PATH = '/partial';
    const ADMIN_PATH = '/admin.php/foo';

    public static bool $web_include_partial = false;

    private PHPFileRouteLoader $file_loader;

    private string $base_prefix = '/sniccowp';
    private string $bad_routes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file_loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions('')
        );
        $this->withMiddlewareGroups(['frontend' => [FooMiddleware::class]]);
        $this->withMiddlewareAlias(['partial' => BarMiddleware::class]);
        self::$web_include_partial = false;
        $this->bad_routes = dirname(__DIR__) . '/fixtures/bad-routes';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        self::$web_include_partial = false;
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_if_one_route_dir_is_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('string.');

        $this->file_loader->loadRoutesIn([$this->routes_dir, 1]);
    }

    /**
     * @test
     */
    public function test_exception_if_one_route_dir_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('readable');

        $this->file_loader->loadRoutesIn([$this->routes_dir, __DIR__ . '/bogus']);
    }

    /**
     * @test
     */
    public function all_php_files_in_the_route_directory_are_loaded(): void
    {
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $response = $this->runKernel($this->frontendRequest(self::WEB_PATH));
        $response->assertOk()->assertNotDelegated();
    }

    /**
     * @test
     */
    public function a_middleware_matching_the_filename_is_added_to_all_routes(): void
    {
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(self::WEB_PATH)
        );
    }

    /**
     * @test
     */
    public function no_name_prefix_is_added_to_frontend_routes(): void
    {
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $this->assertSame(self::WEB_PATH, $this->generator()->toRoute('web1'));

        $this->expectException(RouteNotFound::class);
        $this->generator()->toRoute('frontend.web1');
    }

    /**
     * @test
     */
    public function files_that_start_with_an_underscore_wont_be_included(): void
    {
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $response = $this->runKernel($this->frontendRequest(self::PARTIAL_PATH));

        // Not the partial route but the fallback route in web.php
        $this->assertSame('fallback:partial:foo_middleware', $response->body());
    }

    /**
     * @test
     */
    public function files_that_start_with_an_underscore_can_be_included_in_other_files(): void
    {
        self::$web_include_partial = true;

        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $response = $this->runKernel($this->frontendRequest(self::WEB_PATH));
        $response->assertOk()->assertNotDelegated();

        $response = $this->runKernel($this->frontendRequest(self::PARTIAL_PATH));
        $response->assertOk()->assertNotDelegated();
    }

    /**
     * @test
     */
    public function included_partials_will_receive_all_delegated_attributes_from_the_including_route_file(): void
    {
        self::$web_include_partial = true;

        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        // Partial has no attributes from partial
        $this->assertResponseBody(
            RoutingTestController::static . ':foo_middleware',
            $this->frontendRequest(self::WEB_PATH)
        );

        // Partial has middleware config from parent.
        $this->assertResponseBody(
            RoutingTestController::static . ':bar_middleware:foo_middleware',
            $this->frontendRequest(self::PARTIAL_PATH)
        );
    }

    /**
     * @test
     */
    public function all_files_in_the_api_dir_will_be_included_and_prefixed_with_the_base_prefix(): void
    {
        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions(
                $this->base_prefix,
            )
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $response = $this->runKernel(
            $this->frontendRequest($this->base_prefix . '/partials/cart')
        );

        $response->assertOk()->assertNotDelegated();
    }

    /**
     * @test
     */
    public function all_files_in_the_api_dir_have_the_file_name_as_a_route_name_prefix(): void
    {
        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions(
                $this->base_prefix,
            )
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $this->assertSame(
            '/sniccowp/partials/cart',
            $this->generator()->toRoute('api.partials.cart')
        );
    }

    /**
     * @test
     */
    public function all_api_routes_have_an_api_middleware_appended_by_default(): void
    {
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::API_MIDDLEWARE => [FooMiddleware::class],
                'partials' => [BarMiddleware::class],
            ]
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions(
                $this->base_prefix,
            )
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $response = $this->runKernel(
            $this->frontendRequest($this->base_prefix . '/partials/cart')
        );

        // Bar middleware is not included by default
        $response->assertOk()->assertBodyExact(RoutingTestController::static . ':foo_middleware');
    }

    /**
     * @test
     */
    public function the_filename_can_be_added_as_a_middleware_for_api_routes(): void
    {
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::API_MIDDLEWARE => [FooMiddleware::class],
                'partials' => [BarMiddleware::class],
            ]
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions(
                $this->base_prefix,
                true
            )
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $response = $this->runKernel(
            $this->frontendRequest($this->base_prefix . '/partials/cart')
        );

        // Bar middleware is not included by default
        $response->assertOk()->assertBodyExact(
            RoutingTestController::static . ':bar_middleware:foo_middleware'
        );
    }

    /**
     * @test
     */
    public function if_a_path_contains_a_version_flag_it_will_be_appended_to_the_prefix_and_name(): void
    {
        $this->withMiddlewareGroups(['partials' => [], 'rest.v1' => []]);

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new DefaultRouteLoadingOptions(
                $this->base_prefix,
            )
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $response = $this->runKernel(
            $this->frontendRequest($this->base_prefix . '/rest/v1/posts')
        );

        $response->assertOk()->assertNotDelegated();

        $this->assertSame(
            '/sniccowp/rest/v1/posts',
            $this->generator()->toRoute('api.rest.v1.posts')
        );
    }

    /**
     * @test
     */
    public function the_api_options_can_be_customized(): void
    {
        // We did not add middleware

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new TestLoadingOptions()
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);

        $response = $this->runKernel(
            $this->frontendRequest('/rest/posts')
        );
        $response->assertOk()->assertNotDelegated();

        $this->assertSame(
            '/rest/posts',
            $this->generator()->toRoute('rest.posts')
        );

        $response = $this->runKernel(
            $this->frontendRequest('/partials/cart')
        );
        $response->assertOk()->assertNotDelegated();

        $this->assertSame(
            '/partials/cart',
            $this->generator()->toRoute('partials.cart')
        );
    }

    /**
     * @test
     */
    public function test_exception_if_api_options_has_middleware_but_not_as_an_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware for api options');

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new TestLoadingOptions(true)

        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function test_exception_if_api_options_has_middleware_but_not_all_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware for api options has to be an array of strings.');

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new TestLoadingOptions(false, true)
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function test_exception_if_prefix_is_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                '[%s] has to be a string that starts with a forward slash.',
                RoutingConfigurator::PREFIX_KEY
            )
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new ConfigurableLoadingOptions([
                RoutingConfigurator::PREFIX_KEY => 'abc',
            ])
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function test_exception_if_namespace_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                '[%s] has to be a non-empty string.',
                RoutingConfigurator::NAMESPACE_KEY
            )
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new ConfigurableLoadingOptions([
                RoutingConfigurator::NAMESPACE_KEY => 1,
            ])
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function test_exception_if_name_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                '[%s] has to be a non-empty string.',
                RoutingConfigurator::NAME_KEY
            )
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new ConfigurableLoadingOptions([
                RoutingConfigurator::NAME_KEY => 1,
            ])
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function test_exception_if_argument_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The option [bogus] is not supported.',
        );

        $loader = new PHPFileRouteLoader(
            $this->routeConfigurator(),
            new ConfigurableLoadingOptions([
                'bogus' => 'foo',
            ])
        );

        $loader->loadApiRoutesIn([$this->routes_dir . '/api']);
    }

    /**
     * @test
     */
    public function a_file_named_admin_has_the_admin_middleware_and_prefix_prepended(): void
    {
        $this->withMiddlewareGroups(['admin' => [FooMiddleware::class]]);
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $response = $this->runKernel($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $response->assertOk()->assertNotDelegated();
        $this->assertSame(RoutingTestController::static . ':foo_middleware', $response->body());

        $this->assertSame(
            '/wp-admin/admin.php?page=foo',
            $this->generator()->toRoute('admin.admin_route_1')
        );
    }

    /**
     * @test
     */
    public function a_returned_closure_without_a_typehint_will_thrown_an_exception(): void
    {
        $this->expectExceptionMessage('needs to have an instance of');
        $this->expectException(InvalidArgumentException::class);

        $this->file_loader->loadRoutesIn([$this->bad_routes . '/no-typehint']);
    }

    /**
     * @test
     */
    public function a_returned_closure_without_parameters_will_throw_an_exception(): void
    {
        $this->expectExceptionMessage('needs to have an instance of');
        $this->expectException(InvalidArgumentException::class);

        $this->file_loader->loadRoutesIn([$this->bad_routes . '/no-param']);
    }

    /**
     * @test
     */
    public function a_returned_closure_with_two_params_will_throw_an_exception(): void
    {
        $this->expectExceptionMessage('will only receive');
        $this->expectException(InvalidArgumentException::class);

        $this->file_loader->loadRoutesIn([$this->bad_routes . '/two-params']);
    }

    /**
     * @test
     */
    public function the_first_argument_of_the_returned_closure_is_enforced_to_be_an_admin_configurator_for_the_admin_routes(
    ): void
    {
        $this->expectExceptionMessage(
            sprintf(
                'but required [%s]',
                WebRoutingConfigurator::class
            )
        );
        $this->expectException(LogicException::class);

        $this->file_loader->loadRoutesIn([$this->bad_routes . '/admin']);
    }

    /**
     * @test
     */
    public function the_first_argument_for_non_admin_routes_can_not_be_an_admin_configurator(): void
    {
        $this->expectExceptionMessage(
            sprintf(
                'but required [%s]',
                AdminRoutingConfigurator::class
            )
        );
        $this->expectException(LogicException::class);

        $this->file_loader->loadRoutesIn([$this->bad_routes . '/web-route-uses-admin']);
    }

    /**
     * @test
     */
    public function the_web_route_file_is_always_loaded_last(): void
    {
        $this->file_loader->loadRoutesIn([$this->routes_dir]);

        $this->runKernel($this->frontendRequest('/first'))->assertOk();
    }

    /**
     * @test
     */
    public function test_exception_if_web_file_in_api_route_dir(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[frontend.php] is a reserved filename and can not be loaded as an API file.'
        );
        $this->file_loader->loadApiRoutesIn([$this->bad_routes . '/web-in-api-dir']);
    }

}

class TestLoadingOptions implements RouteLoadingOptions
{

    private bool $fail_because_of_array;
    private bool $fail_because_of_wrong_type;

    public function __construct(bool $fail_because_of_array = false, bool $fail_because_of_wrong_type = false)
    {
        $this->fail_because_of_array = $fail_because_of_array;
        $this->fail_because_of_wrong_type = $fail_because_of_wrong_type;
    }

    public function getApiRouteAttributes(
        string $file_basename,
        ?string $parsed_version
    ): array {
        $att = [
            'prefix' => '/' . $file_basename,
            'name' => $file_basename,
        ];

        if ($this->fail_because_of_array) {
            $att['middleware'] = 'foo';
        }
        if ($this->fail_because_of_wrong_type) {
            $att['middleware'] = ['foo', 1];
        }

        return $att;
    }

    public function getRouteAttributes(string $file_basename): array
    {
        return [];
    }

}

class ConfigurableLoadingOptions implements RouteLoadingOptions
{

    private array $return_api;
    private array $return_normal;

    public function __construct(array $return_api, array $return_normal = [])
    {
        $this->return_api = $return_api;
        $this->return_normal = $return_normal;
    }

    public function getApiRouteAttributes(
        string $file_basename,
        ?string $parsed_version
    ): array {
        return $this->return_api;
    }

    public function getRouteAttributes(string $file_basename): array
    {
        return $this->return_normal;
    }

}