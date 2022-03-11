<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Http;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\RedirectResponse;
use Snicco\Component\HttpRouting\Http\ResponseUtils;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;
use Snicco\Component\HttpRouting\Tests\helpers\CreateUrlGenerator;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class ResponseUtilsTest extends TestCase
{
    use CreateTestPsr17Factories;
    use CreateUrlGenerator;
    use CreatesPsrRequests;

    private ResponseUtils $response_utils;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response_utils = new ResponseUtils(
            $this->createUrlGenerator(),
            $this->createResponseFactory(),
            $this->request = $this->frontendRequest()
        );
    }

    /**
     * @test
     */
    public function test_redirect_to(): void
    {
        $response = $this->response_utils->redirectTo('/foo', 303, [
            'baz' => 'biz',
        ]);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/foo?baz=biz', $response->getHeaderLine('location'));
        $this->assertSame(303, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_refresh(): void
    {
        $request = $this->frontendRequest('https://foo.com/bar?baz=biz#section1');
        $response_utils = new ResponseUtils($this->createUrlGenerator(), $this->createResponseFactory(), $request);

        $this->assertSame(
            'https://foo.com/bar?baz=biz#section1',
            $response_utils->refresh()
                ->getHeaderLine('location')
        );
    }

    /**
     * @test
     */
    public function test_redirect_away(): void
    {
        $response = $this->response_utils->externalRedirect('https://location.com', 303);

        $this->assertSame('https://location.com', $response->getHeaderLine('location'));
        $this->assertSame(303, $response->getStatusCode());
        $this->assertTrue($response->isExternalRedirectAllowed());
    }

    /**
     * @test
     */
    public function test_redirect_to_route(): void
    {
        $route = Route::create('/foo/{param}', Route::DELEGATE, 'route1');

        $routes = new RouteCollection([$route]);

        $response_utils = new ResponseUtils(
            $this->createUrlGenerator(null, $routes),
            $this->createResponseFactory(),
            $this->frontendRequest()
        );

        $response = $response_utils->redirectToRoute('route1', [
            'param' => 'bar',
        ], 303);
        $this->assertSame('/foo/bar', $response->getHeaderLine('location'));
        $this->assertSame(303, $response->getStatusCode());

        $this->expectException(RouteNotFound::class);
        $response_utils->redirectToRoute('route2', [
            'param' => 'bar',
        ], 303);
    }

    /**
     * @test
     */
    public function test_redirect_home_goes_to_the_home_route_if_it_exists(): void
    {
        $home_route = Route::create('/home/{user_id}', Route::DELEGATE, 'home');
        $routes = new RouteCollection([$home_route]);

        $response_utils = new ResponseUtils(
            $this->createUrlGenerator(null, $routes),
            $this->createResponseFactory(),
            $this->frontendRequest()
        );

        $response = $response_utils->redirectHome([
            'user_id' => 1,
            'foo' => 'bar',
        ], 307);

        $this->assertSame('/home/1?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect_home_with_no_home_route_defaults_to_the_base_path(): void
    {
        $response = $this->response_utils->redirectHome([
            'foo' => 'bar',
        ], 307);

        $this->assertSame('/?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect_to_login(): void
    {
        $response = $this->response_utils->redirectToLogin([
            'foo' => 'bar',
        ], 307);

        $this->assertSame('/wp-login.php?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect_to_login_with_route(): void
    {
        $home_route = Route::create('/login', Route::DELEGATE, 'auth.login');
        $routes = new RouteCollection([$home_route]);

        $response_utils = new ResponseUtils(
            $this->createUrlGenerator(null, $routes),
            $this->createResponseFactory(),
            $this->frontendRequest()
        );

        $response = $response_utils->redirectToLogin([
            'foo' => 'bar',
        ], 307);

        $this->assertSame('/login?foo=bar', $response->getHeaderLine('location'));
        $this->assertSame(307, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect_back_with_referer_header(): void
    {
        $response_utils = new ResponseUtils(
            $this->createUrlGenerator(),
            $this->createResponseFactory(),
            $this->frontendRequest()
                ->withHeader('referer', 'https://foo.com/bar')
        );

        $response = $response_utils->redirectBack('/foobar');

        $this->assertSame('https://foo.com/bar', $response->getHeaderLine('location'));
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_redirect_back_without_referer(): void
    {
        $response = $this->response_utils->redirectBack();
        $this->assertSame('/', $response->getHeaderLine('location'));
        $this->assertSame(302, $response->getStatusCode());

        $response = $this->response_utils->redirectBack('/fallback');
        $this->assertSame('/fallback', $response->getHeaderLine('location'));
        $this->assertSame(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function test_view(): void
    {
        $response = $this->response_utils->view('view.php', [
            'foo' => 'bar',
        ]);

        $this->assertSame('view.php', $response->view());
        $this->assertEquals([
            'foo' => 'bar',
            'request' => $this->request,
        ], $response->viewData());
    }

    /**
     * @test
     */
    public function test_html(): void
    {
        $response = $this->response_utils->html('foo');

        $this->assertSame('foo', (string) $response->getBody());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeaderline('content-type'));
    }

    /**
     * @test
     */
    public function test_json(): void
    {
        $response = $this->response_utils->json([
            'foo' => 'bar',
        ]);

        $this->assertSame(json_encode([
            'foo' => 'bar',
        ], JSON_THROW_ON_ERROR), (string) $response->getBody());
        $this->assertSame('application/json', $response->getHeaderline('content-type'));
    }
}
