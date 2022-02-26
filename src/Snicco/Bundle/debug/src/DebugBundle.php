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
use function array_replace;
use function copy;
use function dirname;
use function is_file;

final class DebugBundle implements Bundle
{

    public const ALIAS = 'sniccowp/debug-bundle';

    public function shouldRun(Environment $env): bool
    {
        return $env->isDevelop() && $env->isDebug();
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        if ($kernel->usesBundle('sniccowp/http-routing-bundle')) {
            $this->configureHttpRouting($config, $kernel);
        }
    }

    public function register(Kernel $kernel): void
    {
        if ($kernel->usesBundle('sniccowp/http-routing-bundle')) {
            $this->registerHttpDebugServices($kernel);
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

    private function configureHttpRouting(WritableConfig $config, Kernel $kernel): void
    {
        $config->prepend(HttpErrorHandlingOption::KEY . '.' . HttpErrorHandlingOption::DISPLAYERS, [
            WhoopsJsonDisplayer::class,
            WhoopsHtmlDisplayer::class
        ]);

        $defaults = require dirname(__DIR__) . '/config/debug.php';

        if (!is_file($to = $kernel->directories()->configDir() . '/debug.php')) {
            $copied = copy(
                dirname(__DIR__) . '/config/debug.php',
                $to
            );
            if (false === $copied) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not copy default debug.php config.');
                // @codeCoverageIgnoreEnd
            }
        }

        $defaults = array_merge($defaults, [
            DebugOption::APPLICATION_PATHS => $this->allDirectoriesExpectVendor($kernel->directories())
        ]);

        $config->set('debug', array_replace($defaults, $config->getArray('debug', [])));
    }

    private function registerHttpDebugServices(Kernel $kernel): void
    {
        // private
        $kernel->container()->factory(Run::class, function () {
            $whoops = new Run();
            $whoops->allowQuit(false);
            $whoops->writeToOutput(false);
            return $whoops;
        });

        $kernel->container()->singleton(PrettyPageHandler::class, function () use ($kernel) {
            $handler = new FilterablePrettyPageHandler();
            $handler->handleUnconditionally(true);

            $handler->setEditor($kernel->config()->getString('debug.' . DebugOption::EDITOR));

            $handler->setApplicationRootPath($kernel->directories()->baseDir());

            $handler->setApplicationPaths(
                $kernel->config()->getListOfStrings('debug.' . DebugOption::APPLICATION_PATHS)
            );
            return $handler;
        });

        $kernel->container()->singleton(WhoopsHtmlDisplayer::class, function () use ($kernel) {
            $whoops = $kernel->container()->make(Run::class);
            $whoops->pushHandler($kernel->container()->make(PrettyPageHandler::class));
            return new WhoopsHtmlDisplayer($whoops);
        });

        $kernel->container()->singleton(WhoopsJsonDisplayer::class, function () use ($kernel) {
            $whoops = $kernel->container()->make(Run::class);

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
         * @var string $name
         * @var SplFileInfo $file_info
         */
        foreach ($iterator as $name => $file_info) {
            if ($file_info->isDir() && $file_info->getBasename() !== 'vendor') {
                $dirs[] = $name;
            }
        }

        return $dirs;
    }
}