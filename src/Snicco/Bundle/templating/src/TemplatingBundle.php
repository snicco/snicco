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
use Snicco\Component\Kernel\ValueObject\Directories;
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
use function sprintf;

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

        $this->copyConfiguration($kernel);

        $kernel->afterConfiguration(function (WritableConfig $config) use ($kernel) {
            foreach ($config->getListOfStrings('templating.directories') as $directory) {
                $this->relativeToAbsDirPath($directory, $kernel->directories());
            }
        });
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
        $config = $kernel->config();
        $dirs = $kernel->directories();

        $kernel->container()
            ->shared(PHPViewFactory::class, fn (): PHPViewFactory => new PHPViewFactory(
                $kernel->container()
                    ->make(ViewContextResolver::class),
                array_map(
                    fn (string $dir) => $this->relativeToAbsDirPath($dir, $dirs),
                    $config->getListOfStrings('templating.' . TemplatingOption::DIRECTORIES)
                ),
                $config->getInteger('templating.' . TemplatingOption::PARENT_VIEW_PARSE_LENGTH)
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

    private function relativeToAbsDirPath(string $directory_initial, Directories $kernel_directories): string
    {
        if (is_readable($directory_initial)) {
            return $directory_initial;
        }

        $directory_absolute = "{$kernel_directories->baseDir()}/{$directory_initial}";

        if (! is_readable($directory_absolute)) {
            throw new InvalidArgumentException(
                sprintf(
                    'templating.directories: Directory is not readable. Tried: [%s] and [%s]',
                    $directory_initial,
                    $directory_absolute
                )
            );
        }

        return $directory_absolute;
    }
}
