<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\ValueObject\Environment;

interface Bootstrapper
{
    /**
     * This is the first method that is called on every bundle and bootstrapper.
     * Returning false will not run any further code.
     */
    public function shouldRun(Environment $env): bool;

    /**
     * This method will be called after the application config has been loaded.
     * This is method is the place to validate and or extend configuration
     * values. It will be called before the register method. When the
     * configuration is cached this method will NOT be called.
     */
    public function configure(WritableConfig $config, Kernel $kernel): void;

    /**
     * The register method will be called for each bundle after the configure
     * method has been called for each bundle. This method should be used to
     * bind services into the applications DI container. No services should be
     * created here, and it's not safe to depend on services that will be bound
     * inside other bundle. You can however see if other bundle are used since
     * their configure method will be called before this method.
     */
    public function register(Kernel $kernel): void;

    /**
     * The bootstrap method will be called for each bundle after the register
     * method has been called on each plugin. This is the place to make "things
     * happen" (if needed) and instantiate services.
     *
     * @note The service container is locked at this point. No services can be registered.
     */
    public function bootstrap(Kernel $kernel): void;
}
