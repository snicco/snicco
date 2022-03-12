<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class RedirectRoutesTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function a_redirect_route_can_be_created(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->redirect('/foo', '/bar', 307, [
                'baz' => 'biz',
            ]);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);

        $response->assertStatus(307)
            ->assertLocation('/bar?baz=biz');
    }

    /**
     * @test
     */
    public function a_permanent_redirect_can_be_created(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->permanentRedirect('/foo', '/bar', [
                'baz' => 'biz',
            ]);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);

        $response->assertStatus(301)
            ->assertLocation('/bar?baz=biz');
    }

    /**
     * @test
     */
    public function a_temporary_redirect_can_be_created(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->temporaryRedirect('/foo', '/bar', [
                'baz' => 'biz',
            ]);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);

        $response->assertStatus(307)
            ->assertLocation('/bar?baz=biz');
    }

    /**
     * @test
     */
    public function a_redirect_to_an_external_url_can_be_created(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->redirectAway('/foo', 'https://foobar.com', 301);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('', $request);

        $response = $this->runNewPipeline($request);

        $response->assertRedirect('https://foobar.com', 301);
    }

    /**
     * @test
     */
    public function a_redirect_to_a_route_can_be_created(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/base/{param}');
            $configurator->redirectToRoute('/foo', 'route1', [
                'param' => 'baz',
            ], 303);
        });

        $request = $this->frontendRequest('/foo');

        $response = $this->runNewPipeline($request);

        $response->assertStatus(303);
        $response->assertLocation('/base/baz');
    }

    /**
     * @test
     */
    public function regex_based_redirects_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->redirect('base/{slug}', 'base/new')
                ->requireOneOf('slug', ['foo', 'bar']);

            $configurator->get('r1', 'base/biz', RoutingTestController::class);
        });

        $request = $this->frontendRequest('base/foo');
        $response = $this->runNewPipeline($request);
        $response->assertRedirect('/base/new');

        $request = $this->frontendRequest('base/bar');
        $response = $this->runNewPipeline($request);
        $response->assertRedirect('/base/new');

        $request = $this->frontendRequest('base/biz');
        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertSeeText(RoutingTestController::static);
    }
}
