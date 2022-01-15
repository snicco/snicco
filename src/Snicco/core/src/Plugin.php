<?php

declare(strict_types=1);

namespace Snicco\Core;

/**
 * @api
 */
interface Plugin extends Bootstrapper
{
    
    /**
     * The alias of your plugin is used in various places during the bootstrapping process.
     * App aliases MUST BE UNIQUE per application and MUST be considered part of the public API
     * that a plugin offers. Changing the plugins alias IS A MAYOR BC break. As a best practices
     * the plugins alias should be set to the composer identifier on packagist.
     */
    public function alias() :string;
    
    /**
     * This is the first method that is called on every plugin.
     * Returning false means the plugin will not load any code at all.
     */
    public function runsInEnvironments(Environment $env) :bool;
    
}