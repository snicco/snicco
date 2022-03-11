<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Bundle\Templating\TemplatingMiddleware;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

/**
 * @internal
 */
final class TemplatingMiddlewareTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function the_templating_middleware_works(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [
                    HttpRoutingBundle::class,
                    BetterWPHooksBundle::class,
                    TemplatingBundle::class,
                ],
            ]);
            $config->set('templating.directories', [__DIR__ . '/fixtures/templates']);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->get(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('GET', '/'));

        $response = $pipeline
            ->send($request)
            ->through([TemplatingMiddleware::class, CreateViewResponseMiddleware::class])
            ->then(function () {
                throw new RuntimeException('pipeline exhausted');
            });

        $this->assertSame('bar', (string) $response->getBody());
        $this->assertNotInstanceOf(ViewResponse::class, $response);
    }

    /**
     * @test
     */
    public function non_view_responses_are_not_affected(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [
                    HttpRoutingBundle::class,
                    BetterWPHooksBundle::class,
                    TemplatingBundle::class,
                ],
            ]);
            $config->set('templating.directories', [__DIR__ . '/fixtures/templates']);
        });

        $kernel->boot();

        /** @var MiddlewarePipeline $pipeline */
        $pipeline = $kernel->container()
            ->get(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('GET', '/'));

        $response = $pipeline
            ->send($request)
            ->through([TemplatingMiddleware::class])
            ->then(function () {
                return new Response(200, [
                    'location' => '/foo',
                ]);
            });

        $this->assertSame('', (string) $response->getBody());
        $this->assertSame('/foo', $response->getHeaderLine('location'));
        $this->assertNotInstanceOf(ViewResponse::class, $response);
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}

class CreateViewResponseMiddleware extends Middleware
{
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        return $this->respondWith()
            ->view('foo', [
                'foo' => 'bar',
            ]);
    }
}
