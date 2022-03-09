<?php

declare(strict_types=1);


namespace Snicco\Bundle\Blade;

use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Bridge\Blade\BladeViewFactory;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;

use function is_dir;

final class BladeBundle implements Bundle
{
    public const ALIAS = 'sniccowp/blade-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        //
    }

    public function register(Kernel $kernel): void
    {
        if (! $kernel->usesBundle('sniccowp/templating-bundle')) {
            throw new RuntimeException(BladeBundle::ALIAS . ' needs sniccowp/templating-bundle to run.');
        }

        $container = $kernel->container();
        $container->shared(BladeViewFactory::class, function () use ($kernel) {
            $composers = $kernel->container()->make(ViewComposerCollection::class);

            $dir = $kernel->directories()->cacheDir() . '/blade';
            if (! is_dir($dir)) {
                mkdir($dir, 0775);
            }

            $blade = new BladeStandalone(
                $dir,
                $kernel->config()->getListOfStrings('templating.' . TemplatingOption::DIRECTORIES),
                $composers
            );

            $blade->boostrap();
            $blade->bindWordPressDirectives();

            return $blade->getBladeViewFactory();
        });
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }
}
