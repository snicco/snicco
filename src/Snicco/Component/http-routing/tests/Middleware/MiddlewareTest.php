<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\HttpRouting\Renderer\FileTemplateRenderer;
use Snicco\Component\HttpRouting\Renderer\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RuntimeRouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Testing\CreatesPsrRequests;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use function dirname;

final class MiddlewareTest extends TestCase
{

    use CreateTestPsr17Factories;
    use CreatesPsrRequests;

    private Container $pimple;
    private \Pimple\Psr11\Container $pimple_psr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pimple = new Container();
        $this->pimple_psr = new \Pimple\Psr11\Container($this->pimple);
    }


    /**
     * @test
     */
    public function test_redirector_can_be_used(): void
    {
        $middleware = new class extends Middleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->redirect()->to('/foo');
            }
        };
        $middleware->setContainer($this->pimple_psr);
        $this->pimple[Redirector::class] = function (): ResponseFactory {
            return $this->createResponseFactory($this->getUrLGenerator());
        };

        $response = $middleware->handle($this->frontendRequest(), $this->getNext());

        $this->assertSame('/foo', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_url_generator_can_be_used(): void
    {
        $middleware = new class extends Middleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->respond()->redirect($this->url()->to('/foo', ['bar' => 'baz']));
            }
        };
        $middleware->setContainer($this->pimple_psr);
        $this->pimple[ResponseFactory::class] = function (): ResponseFactory {
            return $this->createResponseFactory($this->getUrLGenerator());
        };
        $this->pimple[UrlGenerator::class] = function (): UrlGenerator {
            return $this->getUrLGenerator();
        };

        $response = $middleware->handle($this->frontendRequest(), $this->getNext());

        $this->assertSame('/foo?bar=baz', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_template_renderer_can_be_used(): void
    {
        $middleware = new class extends Middleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $this->render(dirname(__DIR__, 1) . '/fixtures/templates/greeting.php', ['greet' => 'Calvin'])
                    ->withHeader(
                        'foo',
                        'bar'
                    );
            }
        };
        $middleware->setContainer($this->pimple_psr);
        $this->pimple[ResponseFactory::class] = function (): ResponseFactory {
            return $this->createResponseFactory($this->getUrLGenerator());
        };
        $this->pimple[TemplateRenderer::class] = function (): FileTemplateRenderer {
            return new FileTemplateRenderer();
        };

        $response = $middleware->handle($this->frontendRequest(), $this->getNext());

        $this->assertSame('bar', $response->getHeaderLine('foo'));
        $this->assertSame('Hello Calvin', (string)$response->getBody());
    }

    /**
     * @test
     */
    public function test_process_can_be_called_with_normal_psr_interface(): void
    {
        $middleware = new class extends Middleware {

            public function handle(Request $request, NextMiddleware $next): ResponseInterface
            {
                return $next($request);
            }
        };
        $middleware->setContainer($this->pimple_psr);
        $rf = $this->createResponseFactory($this->getUrLGenerator());
        $this->pimple[ResponseFactory::class] = function () use ($rf): ResponseFactory {
            return $rf;
        };


        $response = $middleware->process(
            $this->psrServerRequestFactory()->createServerRequest('GET', '/foo'),
            new class($rf) implements RequestHandlerInterface {

                private ResponseFactory $factory;

                public function __construct(ResponseFactory $factory)
                {
                    $this->factory = $factory;
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->factory->html('foo');
                }
            }
        );

        $this->assertSame('foo', (string)$response->getBody());
    }

    private function getNext(): NextMiddleware
    {
        return new NextMiddleware(function () {
            throw new RuntimeException('Next should not be called.');
        });
    }

    private function getUrLGenerator(): UrlGenerator
    {
        return new Generator(
            new RuntimeRouteCollection(),
            UrlGenerationContext::forConsole('127.0.0.0'),
            WPAdminArea::fromDefaults()
        );
    }

}

