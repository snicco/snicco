<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function dirname;

class ViewRoutesTest extends HttpRunnerTestCase
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
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->view(
                '/foo',
                $this->view
            );
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);
        $response->assertOk();
        $response->assertIsHtml();

        $this->assertSame('/foo', $routing->urlGenerator()->toRoute('view:greeting.php'));

        $psr = $response->getPsrResponse();
        $this->assertInstanceOf(ViewResponse::class, $psr);
        $this->assertSame($this->view, $psr->view());
    }

    /**
     * @test
     */
    public function the_default_values_can_be_customized_for_view_routes(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator) {
            $configurator->view(
                '/foo',
                $this->view,
                ['greet' => 'Calvin'],
                200,
                ['X-FOO' => 'BAR']
            );
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
        $this->assertSame(['greet' => 'Calvin'], $psr->viewData());
    }

}

