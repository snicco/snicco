<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\HttpRouting\HttpRoutingBundle;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Bundle\Templating\TemplatingExceptionDisplayer;
use Snicco\Bundle\Templating\TemplatingMiddleware;
use Snicco\Bundle\Templating\Tests\fixtures\ViewComposerWithDependency;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use stdClass;

use function dirname;
use function file_put_contents;
use function is_file;
use function spl_object_hash;
use function var_export;

/**
 * @internal
 */
final class TemplatingBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('sniccowp/templating-bundle'));
    }

    /**
     * @test
     */
    public function test_view_engine_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->boot();

        $this->assertCanBeResolved(ViewEngine::class, $kernel);
    }

    /**
     * @test
     */
    public function test_global_context_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertCanBeResolved(GlobalViewContext::class, $kernel);

        /** @var GlobalViewContext $context */
        $context = $kernel->container()
            ->get(GlobalViewContext::class);
        $this->assertTrue(isset($context->get()['view']));
        $this->assertFalse(isset($context->get()['url']));
        $this->assertInstanceOf(ViewEngine::class, $context->get()['view']);
    }

    /**
     * @test
     */
    public function the_url_generator_is_added_to_the_global_context_if_routing_bundle_is_used(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->extend('bundles.all', [HttpRoutingBundle::class, BetterWPHooksBundle::class]);
        });
        $kernel->boot();

        $this->assertCanBeResolved(GlobalViewContext::class, $kernel);

        /** @var GlobalViewContext $context */
        $context = $kernel->container()
            ->get(GlobalViewContext::class);
        $this->assertTrue(isset($context->get()['url']));
        $this->assertInstanceOf(UrlGenerator::class, $context->get()['url']);
    }

    /**
     * @test
     */
    public function test_view_composer_collection_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertCanBeResolved(ViewComposerCollection::class, $kernel);
    }

    /**
     * @test
     */
    public function creating_a_view_with_composers_and_global_context_works(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('templating.directories', [__DIR__ . '/fixtures/templates']);
        });

        $std_class = new stdClass();
        $kernel->afterRegister(function (Kernel $kernel) use ($std_class): void {
            $kernel->container()
                ->shared(
                    ViewComposerWithDependency::class,
                    fn (): ViewComposerWithDependency => new ViewComposerWithDependency($std_class)
                );
        });

        $kernel->boot();

        /**
         * @var ViewComposerCollection $composers
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $composers = $kernel->container()
            ->make(ViewComposerCollection::class);
        $composers->addComposer('*', ViewComposerWithDependency::class);

        /**
         * @var ViewEngine $engine
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $engine = $kernel->container()
            ->make(ViewEngine::class);

        /**
         * @var GlobalViewContext $global_context
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $global_context = $kernel->container()
            ->make(GlobalViewContext::class);
        $global_context->add('foo', 'bar');

        $view = $engine->make('errors.403');

        $view = $composers->compose($view);

        $context = $view->context();
        $this->assertTrue(isset($context['object_hash']));
        $this->assertTrue(isset($context['foo']));

        $this->assertSame(spl_object_hash($std_class), $context['object_hash']);
        $this->assertSame('bar', $context['foo']);
    }

    /**
     * @test
     */
    public function view_composers_can_be_added_in_the_configuration(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('templating', [
                TemplatingOption::DIRECTORIES => [__DIR__ . '/fixtures/templates'],
                TemplatingOption::VIEW_COMPOSERS => [
                    CustomBundleComposer::class => ['foo'],
                ],
            ]);
        });

        $kernel->boot();

        /**
         * @var ViewEngine $engine
         * @psalm-suppress UnnecessaryVarAnnotation
         */
        $engine = $kernel->container()
            ->make(ViewEngine::class);

        $foo_view_string = $engine->render('foo');

        $this->assertSame(CustomBundleComposer::class, $foo_view_string);
    }

    /**
     * @test
     */
    public function the_templating_middleware_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('bundles', [
                Environment::ALL => [
                    HttpRoutingBundle::class,
                    BetterWPHooksBundle::class,
                    TemplatingBundle::class,
                ],
            ]);
        });
        $kernel->boot();

        $this->assertCanBeResolved(TemplatingMiddleware::class, $kernel);
    }

    /**
     * @test
     */
    public function the_templating_middleware_is_not_bound_if_the_http_routing_bundle_is_not_used(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertNotBound(TemplatingMiddleware::class, $kernel);
    }

    /**
     * @test
     */
    public function the_view_engine_exception_displayer_can_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();
        $this->assertNotBound(TemplatingExceptionDisplayer::class, $kernel);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('bundles', [
                Environment::ALL => [
                    HttpRoutingBundle::class,
                    BetterWPHooksBundle::class,
                    TemplatingBundle::class,
                ],
            ]);
        });
        $kernel->boot();

        $this->assertCanBeResolved(TemplatingExceptionDisplayer::class, $kernel);
    }

    /**
     * @test
     */
    public function config_defaults_are_set(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('templating', []);
        });

        $kernel->boot();

        $this->assertSame([PHPViewFactory::class], $kernel->config()->getListOfStrings('templating.factories'));

        $this->assertSame([], $kernel->config()->getListOfStrings('templating.directories'));
    }

    /**
     * @test
     */
    public function test_exception_if_directories_not_readable(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('templating.directories', [__DIR__ . '/bogus']);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('readable');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/templating.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/templating.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/templating.php';

        $this->assertSame(require dirname(__DIR__) . '/config/templating.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/templating.php',
            '<?php return ' . var_export([
                TemplatingOption::DIRECTORIES => [__DIR__],
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/templating.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [
                TemplatingOption::DIRECTORIES => [__DIR__],
            ],
            require $this->directories->configDir() . '/templating.php'
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/templating.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/templating.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}

class CustomBundleComposer implements ViewComposer
{
    public function compose(View $view): View
    {
        return $view->with('foo', self::class);
    }
}
