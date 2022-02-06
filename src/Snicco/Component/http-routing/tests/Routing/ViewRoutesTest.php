<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function dirname;

class ViewRoutesTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function view_routes_work(): void
    {
        $this->routeConfigurator()->view(
            '/foo',
            $this->view
        );

        $request = $this->frontendRequest('/foo');

        $response = $this->runKernel($request);
        $response->assertOk();
        $response->assertSeeHtml('Hello World');
        $response->assertIsHtml();

        $this->assertSame('/foo', $this->generator->toRoute('view:greeting.php'));
    }

    /**
     * @test
     */
    public function the_default_values_can_be_customized_for_view_routes(): void
    {
        $this->routeConfigurator()->view(
            '/foo',
            $this->view,
            ['greet' => 'Calvin'],
            200,
            ['X-FOO' => 'BAR']
        );

        $request = $this->frontendRequest('/foo');

        $response = $this->runKernel($request);
        $response->assertOk();
        $response->assertSeeHtml('Hello Calvin');
        $response->assertIsHtml();
        $response->assertHeader('X-FOO', 'BAR');

        $this->assertSame('/foo', $this->generator->toRoute('view:greeting.php'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->view = dirname(__DIR__) . '/fixtures/templates/greeting.php';
    }

}

