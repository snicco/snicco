<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\RouteLoader;

use InvalidArgumentException;
use LogicException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\RouteLoader\DefaultRouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RouteLoader\PHPFileRouteLoader;
use Snicco\Component\HttpRouting\Routing\RouteLoader\RouteLoadingOptions;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function dirname;

/**
 * @internal
 */
final class PHPFileRouteLoaderTest extends HttpRunnerTestCase
{
    /**
     * @var string
     */
    public const WEB_PATH = '/web';

    /**
     * @var string
     */
    public const PARTIAL_PATH = '/partial';

    /**
     * @var string
     */
    public const ADMIN_PATH = '/admin.php/foo';

    /**
     * @var string
     */
    private const BASE_PREFIX = '/snicco';

    public static bool $web_include_partial = false;

    private string $bad_routes;

    private string $api_routes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withMiddlewareGroups([
            'frontend' => [FooMiddleware::class],
        ]);
        $this->withMiddlewareAlias([
            'partial' => BarMiddleware::class,
        ]);
        self::$web_include_partial = false;
        $this->bad_routes = dirname(__DIR__, 2) . '/fixtures/bad-routes';
        $this->api_routes = $this->routes_dir . '/api';
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
        $this->expectExceptionMessage('readable');

        (new PHPFileRouteLoader([1], [], new DefaultRouteLoadingOptions('')));
    }

    /**
     * @test
     */
    public function test_exception_if_one_route_dir_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('readable');

        new PHPFileRouteLoader([$this->routes_dir, __DIR__ . '/bogus'], [], new DefaultRouteLoadingOptions(''));
    }

    /**
     * @test
     */
    public function php_files_in_the_route_directory_are_loaded(): void
    {
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));

        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::WEB_PATH));
        $response->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function routes_with_the_same_basename_but_different_path_are_both_loaded(): void
    {
        $component_one_routes = $this->routes_dir . '/component-one';
        $component_two_routes = $this->routes_dir . '/component-two';

        $loader = new PHPFileRouteLoader(
            [$component_one_routes, $component_two_routes],
            [],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest('/component-1'));
        $response->assertOk()
            ->assertNotDelegated();

        $response = $this->runNewPipeline($this->frontendRequest('/component-2'));
        $response->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function a_middleware_matching_the_filename_is_added_to_all_routes(): void
    {
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));

        $this->newRoutingFacade($loader);

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
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));

        $routing = $this->newRoutingFacade($loader);

        $this->assertSame(self::WEB_PATH, $routing->urlGenerator()->toRoute('web1'));

        $this->expectException(RouteNotFound::class);
        $this->generator()
            ->toRoute('frontend.web1');
    }

    /**
     * @test
     */
    public function files_that_start_with_an_underscore_wont_be_included(): void
    {
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));

        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::PARTIAL_PATH));

        // Not the partial route but the fallback route in web.php
        $this->assertSame('fallback:partial:foo_middleware', $response->body());
    }

    /**
     * @test
     */
    public function files_that_start_with_an_underscore_can_be_included_in_other_files(): void
    {
        self::$web_include_partial = true;

        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));
        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::WEB_PATH));
        $response->assertOk()
            ->assertNotDelegated();

        $response = $this->runNewPipeline($this->frontendRequest(self::PARTIAL_PATH));
        $response->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function included_partials_will_receive_all_delegated_attributes_from_the_including_route_file(): void
    {
        self::$web_include_partial = true;

        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));
        $this->newRoutingFacade($loader);

        // web has no attributes from partial
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
    public function admin_routes_are_loaded(): void
    {
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));
        $this->newRoutingFacade($loader);

        $this->assertResponseBody(RoutingTestController::static, $this->adminRequest('/wp-admin/admin.php?page=foo'));
    }

    /**
     * @test
     */
    public function all_files_in_the_api_dir_will_be_included_and_prefixed_with_the_base_prefix(): void
    {
        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions(self::BASE_PREFIX)
        );
        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::BASE_PREFIX . '/partials/cart'));

        $response->assertOk()
            ->assertSeeText(RoutingTestController::static);
    }

    /**
     * @test
     */
    public function all_files_in_the_api_dir_have_the_file_name_as_a_route_name_prefix(): void
    {
        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions(self::BASE_PREFIX)
        );
        $routing = $this->newRoutingFacade($loader);

        $this->assertSame('/snicco/partials/cart', $routing->urlGenerator()->toRoute('api.partials.cart'));
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
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions(self::BASE_PREFIX)
        );
        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::BASE_PREFIX . '/partials/cart'));

        // Bar middleware is not included by default
        $response->assertOk()
            ->assertBodyExact(RoutingTestController::static . ':foo_middleware');
    }

    /**
     * @test
     */
    public function if_a_path_contains_a_version_flag_it_will_be_appended_to_the_prefix_and_name(): void
    {
        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions(self::BASE_PREFIX)
        );
        $routing = $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::BASE_PREFIX . '/rest/v1/posts'));

        $response->assertOk()
            ->assertNotDelegated();

        $this->assertSame('/snicco/rest/v1/posts', $routing->urlGenerator()->toRoute('api.rest.v1.posts'));
    }

    /**
     * @test
     */
    public function the_default_options_can_include_the_file_name_as_middleware_for_each_api_file(): void
    {
        $this->withMiddlewareGroups([
            'rest.v1' => [BarMiddleware::class],
            'partials' => [FooMiddleware::class],
        ]);
        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions(self::BASE_PREFIX, true)
        );
        $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->frontendRequest(self::BASE_PREFIX . '/rest/v1/posts'));

        $response->assertSeeText('static:bar_middleware');
    }

    /**
     * @test
     */
    public function the_api_options_can_be_customized(): void
    {
        $loader = new PHPFileRouteLoader([], [$this->api_routes], new TestLoadingOptions());
        $routing = $this->newRoutingFacade($loader);

        // The version flag is not added.
        $response = $this->runNewPipeline($this->frontendRequest('/rest/posts'));
        $response->assertSeeText(RoutingTestController::static);

        // .api is not added to the name
        $this->assertSame('/rest/posts', $routing->urlGenerator()->toRoute('rest.posts'));

        $response = $this->runNewPipeline($this->frontendRequest('/partials/cart'));
        $response->assertSeeText(RoutingTestController::static);

        $this->assertSame('/partials/cart', $routing->urlGenerator()->toRoute('partials.cart'));
    }

    /**
     * @test
     */
    public function test_exception_if_api_options_has_middleware_but_not_as_an_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware for route-loading options');

        $loader = new PHPFileRouteLoader([], [$this->api_routes], new TestLoadingOptions(true));
        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_exception_if_api_options_has_middleware_but_not_all_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Middleware for route-loading options has to be an array of strings.');

        $loader = new PHPFileRouteLoader([], [$this->api_routes], new TestLoadingOptions(false, true));
        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_exception_if_prefix_is_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('[%s] has to be a string that starts with a forward slash.', RoutingConfigurator::PREFIX_KEY)
        );

        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [],
            new ConfigurableLoadingOptions([], [
                RoutingConfigurator::PREFIX_KEY => 'abc',
            ])
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_exception_if_namespace_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('[%s] has to be a non-empty string.', RoutingConfigurator::NAMESPACE_KEY)
        );

        $loader = new PHPFileRouteLoader(
            [],
            [$this->api_routes],
            new ConfigurableLoadingOptions([
                RoutingConfigurator::NAMESPACE_KEY => 1,
            ])
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_exception_if_name_not_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('[%s] has to be a non-empty string.', RoutingConfigurator::NAME_KEY));

        $loader = new PHPFileRouteLoader(
            [],
            [$this->api_routes],
            new ConfigurableLoadingOptions([
                RoutingConfigurator::NAME_KEY => 1,
            ])
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_all_custom_options(): void
    {
        $this->withMiddlewareGroups([
            'custom_middleware' => [FooMiddleware::class],
        ]);

        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new ConfigurableLoadingOptions([], [
                RoutingConfigurator::NAME_KEY => 'foo',
                RoutingConfigurator::NAMESPACE_KEY => self::CONTROLLER_NAMESPACE,
                RoutingConfigurator::MIDDLEWARE_KEY => ['custom_middleware'],
                RoutingConfigurator::PREFIX_KEY => '/custom_prefix',
            ])
        );

        $routing = $this->newRoutingFacade($loader);

        $this->assertResponseBody('static:foo_middleware', $this->frontendRequest('custom_prefix/web'));
        $this->assertSame('/custom_prefix/web', $routing->urlGenerator()->toRoute('foo.web1'));
    }

    /**
     * @test
     */
    public function test_exception_if_argument_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option [bogus] is not supported.', );

        $loader = new PHPFileRouteLoader(
            [],
            [$this->api_routes],
            new ConfigurableLoadingOptions([
                'bogus' => 'foo',
            ])
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function a_file_named_admin_has_the_admin_middleware_and_prefix_prepended(): void
    {
        $this->withMiddlewareGroups([
            'admin' => [FooMiddleware::class],
        ]);
        $loader = new PHPFileRouteLoader(
            [$this->routes_dir],
            [$this->api_routes],
            new DefaultRouteLoadingOptions('')
        );

        $routing = $this->newRoutingFacade($loader);

        $response = $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $response->assertOk()
            ->assertNotDelegated();
        $this->assertSame(RoutingTestController::static . ':foo_middleware', $response->body());

        $this->assertSame(
            '/wp-admin/admin.php?page=foo',
            $routing->urlGenerator()
                ->toRoute('admin.admin_route_1')
        );
    }

    /**
     * @test
     */
    public function a_returned_closure_without_a_typehint_will_thrown_an_exception(): void
    {
        $this->expectExceptionMessage('needs to have an instance of');
        $this->expectException(InvalidArgumentException::class);

        $loader = new PHPFileRouteLoader(
            [$this->bad_routes . '/no-typehint'],
            [],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function a_returned_closure_without_parameters_will_throw_an_exception(): void
    {
        $this->expectExceptionMessage('required [0] parameters');
        $this->expectException(InvalidArgumentException::class);

        $loader = new PHPFileRouteLoader(
            [$this->bad_routes . '/no-param'],
            [],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function a_returned_closure_with_two_params_will_throw_an_exception(): void
    {
        $this->expectExceptionMessage('[2] parameters');
        $this->expectException(InvalidArgumentException::class);

        $loader = new PHPFileRouteLoader(
            [$this->bad_routes . '/two-params'],
            [],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function the_first_argument_of_the_returned_closure_is_enforced_to_be_an_admin_configurator_for_the_admin_routes(
        ): void {
        $this->expectExceptionMessage(sprintf('but required [%s]', WebRoutingConfigurator::class));
        $this->expectException(LogicException::class);

        $loader = new PHPFileRouteLoader([$this->bad_routes . '/admin'], [], new DefaultRouteLoadingOptions(''));

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function the_first_argument_for_non_admin_routes_can_not_be_an_admin_configurator(): void
    {
        $this->expectExceptionMessage(sprintf('but required [%s]', AdminRoutingConfigurator::class));
        $this->expectException(LogicException::class);

        $loader = new PHPFileRouteLoader(
            [$this->bad_routes . '/web-route-uses-admin'],
            [],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function the_web_route_file_is_always_loaded_last(): void
    {
        $loader = new PHPFileRouteLoader([$this->routes_dir], [], new DefaultRouteLoadingOptions(''));

        $this->newRoutingFacade($loader);

        $this->runNewPipeline($this->frontendRequest('/first'))
            ->assertOk()
            ->assertSeeText(RoutingTestController::static);
    }

    /**
     * @test
     */
    public function test_exception_if_web_file_in_api_route_dir(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            '[frontend.php] is a reserved filename and can not be loaded as an API file.'
        );

        $loader = new PHPFileRouteLoader(
            [],
            [$this->bad_routes . '/web-in-api-dir'],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }

    /**
     * @test
     */
    public function test_exception_if_admin_file_in_api_route_dir(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('[admin.php] is a reserved filename and can not be loaded as an API file.');

        $loader = new PHPFileRouteLoader(
            [],
            [$this->bad_routes . '/admin-in-api-dir'],
            new DefaultRouteLoadingOptions('')
        );

        $this->newRoutingFacade($loader);
    }
}

final class TestLoadingOptions implements RouteLoadingOptions
{
    private bool $fail_because_of_array;

    private bool $fail_because_of_wrong_type;

    public function __construct(bool $fail_because_of_array = false, bool $fail_because_of_wrong_type = false)
    {
        $this->fail_because_of_array = $fail_because_of_array;
        $this->fail_because_of_wrong_type = $fail_because_of_wrong_type;
    }

    /**
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
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

final class ConfigurableLoadingOptions implements RouteLoadingOptions
{
    private array $return_api;

    private array $return_normal;

    /**
     * @param mixed[] $return_api
     * @param mixed[] $return_normal
     */
    public function __construct(array $return_api, array $return_normal = [])
    {
        $this->return_api = $return_api;
        $this->return_normal = $return_normal;
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getApiRouteAttributes(string $file_basename, ?string $parsed_version): array
    {
        return $this->return_api;
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function getRouteAttributes(string $file_basename): array
    {
        return $this->return_normal;
    }
}
