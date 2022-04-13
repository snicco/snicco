<?php

declare(strict_types=1);

namespace Snicco\Bundle\Debug;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RuntimeException;
use Snicco\Bundle\Debug\Displayer\WhoopsHtmlDisplayer;
use Snicco\Bundle\Debug\Displayer\WhoopsJsonDisplayer;
use Snicco\Bundle\Debug\Option\DebugOption;
use Snicco\Bundle\HttpRouting\Option\HttpErrorHandlingOption;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use SplFileInfo;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

use function array_merge;
use function copy;
use function dirname;
use function is_file;
use function sprintf;

final class DebugBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/debug-bundle';

    public function shouldRun(Environment $env): bool
    {
        if (! $env->isDevelop()) {
            return false;
        }

        return $env->isDebug();
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        if ($kernel->usesBundle('snicco/http-routing-bundle')) {
            $this->configureHttpRouting($config, $kernel);
        }
    }

    public function register(Kernel $kernel): void
    {
        if ($kernel->usesBundle('snicco/http-routing-bundle')) {
            $this->registerHttpDebugServices($kernel);
        }
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function configureHttpRouting(WritableConfig $config, Kernel $kernel): void
    {
        $config->prependToList('http_error_handling.' . HttpErrorHandlingOption::DISPLAYERS, [
            WhoopsJsonDisplayer::class,
            WhoopsHtmlDisplayer::class,
        ]);

        $defaults = require dirname(__DIR__) . '/config/debug.php';

        $defaults = array_merge($defaults, [
            DebugOption::APPLICATION_PATHS => $this->allDirectoriesExpectVendor($kernel->directories()),
        ]);

        $config->mergeDefaults('debug', $defaults);

        $this->copyConfiguration($kernel);
    }

    private function registerHttpDebugServices(Kernel $kernel): void
    {
        // private
        $kernel->container()
            ->factory(Run::class, function (): Run {
                $whoops = new Run();
                $whoops->allowQuit(false);
                $whoops->writeToOutput(false);

                return $whoops;
            });

        $kernel->container()
            ->shared(PrettyPageHandler::class, function () use ($kernel): FilterablePrettyPageHandler {
                $handler = new FilterablePrettyPageHandler();
                $handler->handleUnconditionally(true);

                $handler->setEditor($kernel->config()->getString('debug.' . DebugOption::EDITOR));

                $handler->setApplicationRootPath($kernel->directories()->baseDir());

                $handler->setApplicationPaths(
                    $kernel->config()
                        ->getListOfStrings('debug.' . DebugOption::APPLICATION_PATHS)
                );

                return $handler;
            });

        $kernel->container()
            ->shared(WhoopsHtmlDisplayer::class, function () use ($kernel): WhoopsHtmlDisplayer {
                $whoops = $kernel->container()
                    ->make(Run::class);
                $whoops->pushHandler($kernel->container()->make(PrettyPageHandler::class));

                return new WhoopsHtmlDisplayer($whoops);
            });

        $kernel->container()
            ->shared(WhoopsJsonDisplayer::class, function () use ($kernel): WhoopsJsonDisplayer {
                $whoops = $kernel->container()
                    ->make(Run::class);

                $handler = new JsonResponseHandler();
                $handler->addTraceToOutput(true);
                $handler->setJsonApi(true);

                $whoops->pushHandler($handler);

                return new WhoopsJsonDisplayer($whoops);
            });
    }

    /**
     * @return list<string>
     */
    private function allDirectoriesExpectVendor(Directories $dirs): array
    {
        $iterator = new RecursiveDirectoryIterator($dirs->baseDir(), FilesystemIterator::SKIP_DOTS);
        $dirs = [];

        /**
         * @var string      $name
         * @var SplFileInfo $file_info
         */
        foreach ($iterator as $name => $file_info) {
            if ($file_info->isDir() && 'vendor' !== $file_info->getBasename()) {
                $dirs[] = $name;
            }
        }

        return $dirs;
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        $destination = $kernel->directories()
            ->configDir() . '/debug.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/debug.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'Could not copy the default templating config to destination [%s]',
                $destination
            ));
            // @codeCoverageIgnoreEnd
        }
    }
}
