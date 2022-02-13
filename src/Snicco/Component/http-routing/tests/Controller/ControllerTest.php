<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Controller\Controller;
use Snicco\Component\HttpRouting\Http\Psr7\DefaultResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Renderer\FileTemplateRenderer;
use Snicco\Component\HttpRouting\Renderer\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RuntimeRouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Tests\helpers\CreateTestPsr17Factories;

use function dirname;

final class ControllerTest extends TestCase
{

    use CreateTestPsr17Factories;

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
        $controller = new class extends Controller {

            public function handle(): ResponseInterface
            {
                return $this->redirect()->to('/foo');
            }
        };
        $controller->setContainer($this->pimple_psr);
        $this->pimple[Redirector::class] = function (): Redirector {
            return $this->createResponseFactory($this->getUrLGenerator());
        };

        $response = $controller->handle();

        $this->assertSame('/foo', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_url_generator_can_be_used(): void
    {
        $controller = new class extends Controller {

            public function handle(): ResponseInterface
            {
                return $this->respond()->redirect($this->url()->to('/foo', ['bar' => 'baz']));
            }
        };
        $controller->setContainer($this->pimple_psr);
        $this->pimple[DefaultResponseFactory::class] = function (): DefaultResponseFactory {
            return $this->createResponseFactory($this->getUrLGenerator());
        };
        $this->pimple[UrlGenerator::class] = function (): UrlGenerator {
            return $this->getUrLGenerator();
        };

        $response = $controller->handle();

        $this->assertSame('/foo?bar=baz', $response->getHeaderLine('location'));
    }

    /**
     * @test
     */
    public function test_template_renderer_can_be_used(): void
    {
        $controller = new class extends Controller {

            public function handle(): ResponseInterface
            {
                return $this->render(dirname(__DIR__, 1) . '/fixtures/templates/greeting.php', ['greet' => 'Calvin'])
                    ->withHeader(
                        'foo',
                        'bar'
                    );
            }
        };
        $controller->setContainer($this->pimple_psr);
        $this->pimple[DefaultResponseFactory ::class] = function (): DefaultResponseFactory {
            return $this->createResponseFactory($this->getUrLGenerator());
        };
        $this->pimple[TemplateRenderer::class] = function (): FileTemplateRenderer {
            return new FileTemplateRenderer();
        };

        $response = $controller->handle();

        $this->assertSame('bar', $response->getHeaderLine('foo'));
        $this->assertSame('Hello Calvin', (string)$response->getBody());
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