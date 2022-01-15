<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use Snicco\Component\Core\Configuration\WritableConfig;

/**
 * @api
 */
interface Bootstrapper
{
    
    /**
     * This method will be called after the application config has been loaded from the disk.
     * This is method is the place to validate and or extend configuration values.
     * It will be called before the register method.
     */
    public function configure(WritableConfig $config, Application $app) :void;
    
    /**
     * The register method will be called for each plugin after the configure method has been
     * called for each plugin.
     * This method should be used to bind services into the applications DI container.
     * No services should be created here, and it's not safe to depend on services that will be
     * bound inside other plugins. You can however see if other plugins are used since their
     * configure method will be called before this method.
     */
    public function register(Application $app) :void;
    
    /**
     * The bootstrap method will be called for each plugin after the register method has been
     * called on each plugin. This is the place to make things happen (if needed) and instantiate
     * services.
     */
    public function bootstrap(Application $app) :void;
    
}