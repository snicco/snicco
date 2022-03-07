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
use Snicco\Component\Templating\GlobalViewContext;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;
use Snicco\Component\Templating\ViewFactory\PHPViewFinder;
use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function array_map;
use function array_replace;
use function copy;
use function dirname;
use function is_file;
use function is_readable;

final class TemplatingBundle implements Bundle
{

    public const ALIAS = 'sniccowp/templating-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/templating.php';
        $config->set(
            'templating',
            array_replace($defaults, $config->getArray('templating', []))
        );

        foreach ($config->getListOfStrings('templating.directories') as $directory) {
            if (!is_readable($directory)) {
                throw new InvalidArgumentException("templating.directories: Directory [$directory] is not readable.");
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

        if ($kernel->usesBundle('sniccowp/http-routing-bundle')) {
            $this->bindTemplatingMiddleware($kernel);
            $this->bindExceptionDisplayer($kernel);
        }
    }

    public function bootstrap(Kernel $kernel): void
    {
        //
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (!$kernel->env()->isDevelop()) {
            return;
        }
        $destination = $kernel->directories()->configDir() . '/templating.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/templating.php', $destination);

        if (false === $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not copy the default templating config to destination [$destination]");
            // @codeCoverageIgnoreEnd
        }
    }

    private function bindViewEngine(Kernel $kernel): void
    {
        $kernel->container()->singleton(ViewEngine::class, function () use ($kernel) {
            $class_names = $kernel->config()->getListOfStrings('templating.' . TemplatingOption::VIEW_FACTORIES);

            $factories = array_map(function (string $class_name) use ($kernel): ViewFactory {
                /** @var class-string<ViewFactory> $class_name */
                return $kernel->container()->make($class_name);
            }, $class_names);

            return new ViewEngine(...$factories);
        });
    }

    private function bindPHPViewFactory(Kernel $kernel): void
    {
        $kernel->container()->singleton(PHPViewFactory::class, function () use ($kernel) {
            return new PHPViewFactory(
                new PHPViewFinder($kernel->config()->getListOfStrings('templating.' . TemplatingOption::DIRECTORIES)),
                $kernel->container()->make(ViewComposerCollection::class),
            );
        });
    }

    private function bindTemplatingMiddleware(Kernel $kernel): void
    {
        $kernel->container()->singleton(TemplatingMiddleware::class, fn() => new TemplatingMiddleware(
            fn() => $kernel->container()->make(ViewEngine::class)
        ));
    }

    private function bindExceptionDisplayer(Kernel $kernel): void
    {
        $kernel->container()->singleton(
            TemplatingExceptionDisplayer::class,
            fn() => new TemplatingExceptionDisplayer($kernel->container()->make(ViewEngine::class))
        );
    }

    private function bindViewComposerCollection(Kernel $kernel): void
    {
        $kernel->container()->singleton(ViewComposerCollection::class, function () use ($kernel) {
            $composer_collection = new ViewComposerCollection(
                new PsrViewComposerFactory($kernel->container()),
                $kernel->container()->make(GlobalViewContext::class)
            );


            /**
             * @var array<class-string<ViewComposer>, list<string>>
             */
            $composers = $kernel->config()->getArray('templating.' . TemplatingOption::VIEW_COMPOSERS);

            foreach ($composers as $class => $views) {
                $composer_collection->addComposer($views, $class);
            }

            return $composer_collection;
        });
    }

    private function bindGlobalViewContext(Kernel $kernel): void
    {
        $kernel->container()->singleton(GlobalViewContext::class, function () use ($kernel) {
            $context = new GlobalViewContext();
            // This needs to be a closure.
            $context->add('view', function () use ($kernel) {
                return $kernel->container()->make(ViewEngine::class);
            });

            if ($kernel->usesBundle('sniccowp/http-routing-bundle')) {
                $context->add('url', fn() => $kernel->container()->make(UrlGenerator::class));
            }

            return $context;
        });
    }
}