<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating;

use InvalidArgumentException;
use RuntimeException;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\Context\GlobalViewContext;
use Snicco\Component\Templating\Context\ViewComposer;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\TemplateEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function array_map;
use function copy;
use function dirname;
use function is_file;
use function is_readable;

final class TemplatingBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/templating-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/templating.php');

        foreach ($config->getListOfStrings('templating.directories') as $directory) {
            if (! is_readable($directory)) {
                throw new InvalidArgumentException(
                    sprintf('templating.directories: Directory [%s] is not readable.', $directory)
                );
            }
        }

        $this->copyConfiguration($kernel);
    }

    public function register(Kernel $kernel): void
    {
        $this->bindViewEngine($kernel);
        $this->bindPHPViewFactory($kernel);
        $this->bindViewComposerCollection($kernel);
        $this->bindGlobalViewContext($kernel);

        if ($kernel->usesBundle('snicco/http-routing-bundle')) {
            $this->bindTemplatingMiddleware($kernel);
            $this->bindExceptionDisplayer($kernel);
        }
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/templating.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/templating.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Could not copy the default templating config to destination [%s]', $destination)
            );
            // @codeCoverageIgnoreEnd
        }
    }

    private function bindViewEngine(Kernel $kernel): void
    {
        $container = $kernel->container();
        $config = $kernel->config();
        $container->shared(TemplateEngine::class, function () use ($container, $config): TemplateEngine {
            /** @var class-string<ViewFactory>[] $class_names */
            $class_names = $config->getListOfStrings('templating.' . TemplatingOption::VIEW_FACTORIES);

            $factories = array_map(
                fn (string $class_name): ViewFactory => $container->make($class_name),
                $class_names
            );

            return new TemplateEngine(...$factories);
        });
    }

    private function bindPHPViewFactory(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(PHPViewFactory::class, fn (): PHPViewFactory => new PHPViewFactory(
                $kernel->container()
                    ->make(ViewContextResolver::class),
                $kernel->config()
                    ->getListOfStrings('templating.' . TemplatingOption::DIRECTORIES)
            ));
    }

    private function bindTemplatingMiddleware(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(TemplatingMiddleware::class, fn (): TemplatingMiddleware => new TemplatingMiddleware(
                fn (): TemplateEngine => $kernel->container()
                    ->make(TemplateEngine::class)
            ));
    }

    private function bindExceptionDisplayer(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(
                TemplatingExceptionDisplayer::class,
                fn (): TemplatingExceptionDisplayer => new TemplatingExceptionDisplayer(
                    $kernel->container()
                        ->make(TemplateEngine::class)
                )
            );
    }

    private function bindViewComposerCollection(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(ViewContextResolver::class, function () use ($kernel): ViewContextResolver {
                $composer_collection = new ViewContextResolver(
                    $kernel->container()
                        ->make(GlobalViewContext::class),
                    new PsrViewComposerFactory($kernel->container())
                );

                $composers = $kernel->config()
                    ->getArray('templating.' . TemplatingOption::VIEW_COMPOSERS);

                /**
                 * @var array<class-string<ViewComposer>,list<string>> $composers
                 */
                foreach ($composers as $class => $views) {
                    $composer_collection->addComposer($views, $class);
                }

                return $composer_collection;
            });
    }

    private function bindGlobalViewContext(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(GlobalViewContext::class, function () use ($kernel): GlobalViewContext {
                $context = new GlobalViewContext();
                // This needs to be a closure.
                $context->add('view', fn (): TemplateEngine => $kernel->container()->make(TemplateEngine::class));

                if ($kernel->usesBundle('snicco/http-routing-bundle')) {
                    $context->add('url', fn (): UrlGenerator => $kernel->container()->make(UrlGenerator::class));
                }

                return $context;
            });
    }
}
