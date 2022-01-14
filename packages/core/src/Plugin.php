<?php

declare(strict_types=1);

namespace Snicco\Core;

use Snicco\Core\Configuration\WritableConfig;

/**
 * @api
 */
abstract class Plugin
{
    
    /**
     * The alias of your plugin is used in various places during the bootstrapping process.
     * App aliases MUST BE UNIQUE per application and MUST be considered part of the public API
     * that a plugin offers. Changing the plugins alias IS A MAYOR BC break. As a best practices
     * the plugins alias should be set to the composer identifier on packagist.
     */
    abstract public function alias() :string;
    
    /**
     * This method will be called after the application config has been loaded from the disk.
     * This is method is the place to validate and or extend configuration values.
     * It will be called before the register method.
     */
    abstract public function configure(WritableConfig $config, Application $app) :void;
    
    /**
     * The register method will be called for each plugin after the configure method has been
     * called for each plugin.
     * This method should be used to bind services into the applications DI container.
     * No services should be created here, and it's not safe to depend on services that will be
     * bound inside other plugins. You can however see if other plugins are used since their
     * configure method will be called before this method.
     */
    abstract public function register(Application $app) :void;
    
    /**
     * The bootstrap method will be called for each plugin after the register method has been
     * called on each plugin. This is the place to make things happen (if needed) and instantiate
     * services.
     */
    abstract public function bootstrap(Application $app) :void;
    
    /**
     * This is the first method that is called on every plugin.
     * Returning false means the plugin will not load any code at all.
     */
    abstract public function runsInEnvironments(Environment $env) :bool;
    
}