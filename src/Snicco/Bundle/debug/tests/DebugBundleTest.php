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
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\MiddlewarePipeline;
use Snicco\Component\HttpRouting\Middleware\RoutingMiddleware;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Exception\MissingConfigKey;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function file_put_contents;
use function is_file;
use function json_decode;
use function var_export;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
final class DebugBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_runs_only_in_dev(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);
        $kernel->boot();

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_only_in_dev_if_debug_is_enabled(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(false), $this->directories);
        $kernel->boot();

        $this->assertFalse($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_runs_in_dev_with_debug_enabled(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(true), $this->directories);
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle(DebugBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_whoops_displayers_are_prepended_if_the_http_routing_bundle_is_used(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS, [StubDisplayer::class]);
        });

        $kernel->boot();

        $displayers = $kernel->config()
            ->getListOfStrings('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS);

        $this->assertSame([
            WhoopsHtmlDisplayer::class,
            WhoopsJsonDisplayer::class,
            StubDisplayer::class,
        ], $displayers);
    }

    /**
     * @test
     */
    public function whoops_displayers_are_not_prepended_if_the_http_routing_bundle_is_not_used(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles', [DebugBundle::class]);
        });

        $kernel->boot();

        $this->expectException(MissingConfigKey::class);

        $kernel->config()
            ->getListOfStrings('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS);
    }

    /**
     * @test
     */
    public function test_whoops_displayer_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->boot();

        $this->assertCanBeResolved(WhoopsHtmlDisplayer::class, $kernel);
    }

    /**
     * @test
     */
    public function test_error_handler_will_use_whoops_for_text_html_requests(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/html');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function (): void {
                throw new RuntimeException('debug stuff');
            });

        $body = (string) $response->getBody();

        // Not using the default handler
        $this->assertStringContainsString('whoops', $body);
        $this->assertStringContainsString('DOCTYPE html', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_use_whoops_for_json_requests(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'application/json');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function (): void {
                throw new RuntimeException('debug stuff');
            });

        $body = (string) $response->getBody();

        /** @var array $decoded */
        $decoded = json_decode($body, true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);

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
        $kernel = new Kernel($this->newContainer(), Environment::dev(false), $this->directories);

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'application/json');

        $response = $pipeline->send($request)
            ->through([RoutingMiddleware::class])
            ->then(function (): void {
                throw new RuntimeException('debug stuff');
            });

        $body = (string) $response->getBody();

        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_not_use_whoops_for_other_accept_headers(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(true), $this->directories);

        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/plain');

        $response = $pipeline->send($request)
            ->through([])->then(function (): void {
                throw new RuntimeException('debug stuff');
            });

        $body = (string) $response->getBody();

        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_error_handler_will_not_use_whoops_if_debug_turned_off(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(false), $this->directories);
        $kernel->boot();

        /**
         * @var MiddlewarePipeline $pipeline
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $pipeline = $kernel->container()
            ->make(MiddlewarePipeline::class);

        $request = Request::fromPsr(new ServerRequest('/GET', '/foo'))
            ->withHeader('accept', 'text/html');

        $response = $pipeline->send($request)
            ->through([])->then(function (): void {
                throw new RuntimeException('debug stuff');
            });

        $body = (string) $response->getBody();

        // Not using the default handler
        $this->assertStringNotContainsString('whoops', $body);
    }

    /**
     * @test
     */
    public function test_debug_editor_defaults_to_phpstorm(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->boot();

        $this->assertSame('phpstorm', $kernel->config()->getString('debug.editor'));
    }

    /**
     * @test
     */
    public function test_application_paths_defaults_are_set(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->boot();

        $paths = $kernel->config()
            ->getListOfStrings('debug.application_paths');

        $this->assertNotEmpty($paths);
        $this->assertNotContains(__DIR__ . '/fixtures/vendor', $paths);
    }

    /**
     * @test
     */
    public function test_application_paths_defaults_are_not_set_if_already_present(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('debug.' . DebugOption::APPLICATION_PATHS, [__DIR__]);
        });

        $kernel->boot();

        $paths = $kernel->config()
            ->getListOfStrings('debug.application_paths');

        $this->assertSame([__DIR__], $paths);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/debug.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/debug.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/debug.php';

        $this->assertSame(require dirname(__DIR__, 1) . '/config/debug.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/debug.php',
            '<?php return ' . var_export([
                'editor' => 'sublime',
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/debug.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame([
            'editor' => 'sublime',
        ], require $this->directories->configDir() . '/debug.php');
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/debug.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/debug.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}
