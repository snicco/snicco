<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function dirname;

/**
 * @internal
 */
final class ViewRoutesTest extends HttpRunnerTestCase
{
    private string $view;

    protected function setUp(): void
    {
        parent::setUp();
        $this->view = dirname(__DIR__, 2) . '/fixtures/templates/greeting.php';
    }

    /**
     * @test
     */
    public function view_routes_work(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->view('/foo', $this->view);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);
        $response->assertOk();
        $response->assertIsHtml();

        $this->assertSame('/foo', $routing->urlGenerator()->toRoute('view:greeting.php'));

        $psr = $response->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);
        $this->assertSame($this->view, $psr->view());

        $request = $this->frontendRequest('/foo', [], 'HEAD');

        $response = $this->runNewPipeline($request);
        $response->assertOk();
        $response->assertIsHtml();
    }

    /**
     * @test
     */
    public function the_default_values_can_be_customized_for_view_routes(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->view('/foo', $this->view, [
                'greet' => 'Calvin',
            ], 200, [
                'X-FOO' => 'BAR',
            ]);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);
        $response->assertOk();
        $response->assertIsHtml();
        $response->assertHeader('X-FOO', 'BAR');

        $this->assertSame('/foo', $routing->urlGenerator()->toRoute('view:greeting.php'));

        $psr = $response->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);
        $this->assertSame($this->view, $psr->view());

        $view_data = $psr->viewData();
        $this->assertTrue(isset($view_data['greet']));
        $this->assertTrue(isset($view_data['request']));
        $this->assertSame('Calvin', $view_data['greet']);
        $this->assertInstanceOf(Request::class, $view_data['request']);
        $this->assertInstanceOf(Route::class, $view_data['request']->routingResult()->route());
    }
}
