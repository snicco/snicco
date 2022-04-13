<?php

declare(strict_types=1);

namespace Snicco\Bundle\Blade;

use Illuminate\Support\Facades\Blade;
use RuntimeException;
use Snicco\Bridge\Blade\BladeStandalone;
use Snicco\Bridge\Blade\BladeViewFactory;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Component\BetterWPAPI\BetterWPAPI;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\Context\ViewContextResolver;

use function in_array;
use function is_array;
use function is_dir;

final class BladeBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/blade-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        if (! $kernel->usesBundle('snicco/templating-bundle')) {
            throw new RuntimeException(BladeBundle::ALIAS . ' needs snicco/templating-bundle to run.');
        }

        $container = $kernel->container();
        $container->shared(BladeViewFactory::class, function () use ($kernel): BladeViewFactory {
            $composers = $kernel->container()
                ->make(ViewContextResolver::class);

            $dir = $kernel->directories()
                ->cacheDir() . '/blade';
            if (! is_dir($dir)) {
                mkdir($dir, 0775);
            }

            $blade = new BladeStandalone(
                $dir,
                $kernel->config()
                    ->getListOfStrings('templating.' . TemplatingOption::DIRECTORIES),
                $composers
            );

            $blade->boostrap();
            $this->bindWordPressBladeDirectives();

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

    private function bindWordPressBladeDirectives(): void
    {
        $wp = new BetterWPAPI();

        Blade::if('auth', fn (): bool => $wp->isUserLoggedIn());

        Blade::if('guest', fn (): bool => ! $wp->isUserLoggedIn());

        Blade::if('role', function (string $expression) use ($wp): bool {
            if ('admin' === $expression) {
                $expression = 'administrator';
            }

            $user = $wp->currentUser();

            return ! empty($user->roles) && is_array($user->roles)
                && in_array($expression, $user->roles, true);
        });
    }
}
