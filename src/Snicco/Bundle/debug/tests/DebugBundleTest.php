<?php

declare(strict_types=1);


namespace Snicco\Bundle\Debug\Tests;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Debug\DebugBundle;
use Snicco\Bundle\Debug\Displayer\WhoopsHtmlDisplayer;
use Snicco\Bundle\Debug\Displayer\WhoopsJsonDisplayer;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Bundle\Testing\BootsKernelForBundleTest;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class DebugBundleTest extends TestCase
{
    use BootsKernelForBundleTest;

    private string $base_dir;
    private Directories $directories;

    /**
     * @var array<'testing'|'prod'|'dev'|'staging'|'all', list< class-string<\Snicco\Component\Kernel\Bundle> >>
     */
    private array $bundles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = __DIR__ . '/fixtures/tmp';
        $this->directories = $this->setUpDirectories($this->base_dir);
        $this->bundles = [
            Environment::ALL => [
                BetterWPHooksBundle::class,
                HttpRoutingBundle::class,
                DebugBundle::class,
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories($this->base_dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_runs_only_in_dev(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_only_in_dev_if_debug_is_enabled(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::dev(false));

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_in_dev_with_debug_enabled(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::dev(true));

        $this->assertTrue($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_whoops_displayers_are_prepended(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

        $displayers = $kernel->config()->getListOfStrings(
            HttpErrorHandlingOption::key(HttpErrorHandlingOption::DISPLAYERS)
        );

        $this->assertSame([
            WhoopsHtmlDisplayer::class,
            WhoopsJsonDisplayer::class
        ], $displayers);
    }

    /**
     * @test
     */
    public function test_whoops_displayer_can_be_resolved(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

        $this->assertCanBeResolved(WhoopsHtmlDisplayer::class, $kernel);
    }

    /**
     * @test
     */
    public function test_error_handler_will_use_whoops_for_text_html_requests(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

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
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

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
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(false)
        );

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
    public function test_error_handler_will_use_not_use_whoops_for_non_html_requests(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

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
    public function test_error_handler_will_not_use_whoops_for_debug_turned_off(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(false)
        );

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
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

        $this->assertSame('phpstorm', $kernel->config()->getString('debug.editor'));
    }

    /**
     * @test
     */
    public function test_application_paths_defaults_are_set(): void
    {
        $kernel = $this->bootWithExtraConfig(
            [],
            Directories::fromDefaults(__DIR__ . '/fixtures'),
            Environment::dev(true)
        );

        $this->assertNotEmpty($kernel->config()->getListOfStrings('debug.application_paths'));
    }

    protected function bundles(): array
    {
        return $this->bundles;
    }

}