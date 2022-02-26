<?php

declare(strict_types=1);


namespace Snicco\Bundle\Debug\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bundle\Debug\DebugBundle;
use Snicco\Bundle\Debug\Displayer\WhoopsHtmlDisplayer;
use Snicco\Bundle\Debug\Displayer\WhoopsJsonDisplayer;
use Snicco\Bundle\Debug\Option\DebugOption;
use Snicco\Bundle\Debug\Tests\fixtures\StubDisplayer;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class DebugBundleTest extends TestCase
{
    use BootsKernelForBundleTest;

    private Directories $directories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directories = Directories::fromDefaults(__DIR__ . '/fixtures');
    }

    protected function tearDown(): void
    {
        $this->removePHPFilesRecursive($this->directories->cacheDir());
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_runs_only_in_dev(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::prod(),
            $this->directories
        );
        $kernel->boot();

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_only_in_dev_if_debug_is_enabled(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(false),
            $this->directories
        );
        $kernel->boot();

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_in_dev_with_debug_enabled(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(true),
            $this->directories
        );
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_whoops_displayers_are_prepended_if_the_http_routing_bundle_is_used(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->beforeConfiguration(function (WritableConfig $config) {
            $config->set('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS, [
                StubDisplayer::class
            ]);
        });

        $kernel->boot();

        $displayers = $kernel->config()->getListOfStrings(
            HttpErrorHandlingOption::key(HttpErrorHandlingOption::DISPLAYERS)
        );

        $this->assertSame([
            WhoopsHtmlDisplayer::class,
            WhoopsJsonDisplayer::class,
            StubDisplayer::class
        ], $displayers);
    }

    /**
     * @test
     */
    public function whoops_displayers_are_not_prepended_if_the_http_routing_bundle_is_not_used(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->beforeConfiguration(function (WritableConfig $config) {
            $config->set('bundles', [
                DebugBundle::class
            ]);
        });

        $kernel->boot();

        $this->expectException(MissingConfigKey::class);

        $kernel->config()->getListOfStrings(
            'http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS
        );
    }

    /**
     * @test
     */
    public function test_whoops_displayer_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        $this->assertCanBeResolved(WhoopsHtmlDisplayer::class, $kernel);
    }

    /**
     * @test
     */
    public function test_error_handler_will_use_whoops_for_text_html_requests(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/html');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function () {
                throw new RuntimeException('debug stuff');
            });

        $body = (string)$response->getBody();

        // Not using the default handler
        $this->assertStringContainsString('whoops', $body);
        $this->assertStringContainsString('DOCTYPE html', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_use_whoops_for_json_requests(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'application/json');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function () {
                throw new RuntimeException('debug stuff');
            });

        $body = (string)$response->getBody();

        /** @var array $decoded */
        $decoded = json_decode($body, true, JSON_THROW_ON_ERROR);

        $this->assertTrue(isset($decoded['errors']));
        $this->assertTrue(isset($decoded['errors'][0]));
        $this->assertIsArray($decoded['errors'][0]);

        // Not using the default handler
        $this->assertStringContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_not_use_whoops_for_json_requests_in_non_debug_mode(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(false),
            $this->directories
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'application/json');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function () {
                throw new RuntimeException('debug stuff');
            });

        $body = (string)$response->getBody();

        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_not_use_whoops_for_other_accept_headers(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(true),
            $this->directories
        );

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/plain');

        $response = $pipeline->send($request)->through([])->then(function () {
            throw new RuntimeException('debug stuff');
        });

        $body = (string)$response->getBody();

        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_not_use_whoops_if_debug_turned_off(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(false),
            $this->directories
        );
        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/html');

        $response = $pipeline->send($request)->through([])->then(function () {
            throw new RuntimeException('debug stuff');
        });

        $body = (string)$response->getBody();

        // Not using the default handler
        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_debug_editor_defaults_to_phpstorm(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        $this->assertSame('phpstorm', $kernel->config()->getString('debug.editor'));
    }

    /**
     * @test
     */
    public function test_application_paths_defaults_are_set(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->boot();

        $paths = $kernel->config()->getListOfStrings('debug.application_paths');

        $this->assertNotEmpty($paths);
        $this->assertNotContains(__DIR__ . '/fixtures/vendor', $paths);
    }

    /**
     * @test
     */
    public function test_application_paths_defaults_are_not_set_if_already_present(): void
    {
        $kernel = new Kernel(
            $this->container(),
            Environment::dev(),
            $this->directories
        );

        $kernel->beforeConfiguration(function (WritableConfig $config) {
            $config->set('debug.' . DebugOption::APPLICATION_PATHS, [__DIR__]);
        });

        $kernel->boot();

        $paths = $kernel->config()->getListOfStrings('debug.application_paths');

        $this->assertSame([__DIR__], $paths);
    }

}