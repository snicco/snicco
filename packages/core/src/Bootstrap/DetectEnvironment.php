<?php

namespace Snicco\Bootstrap;

use Closure;
use Snicco\Support\Str;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;

class DetectEnvironment implements Bootstrapper
{
    
    public function bootstrap(Application $app) :void
    {
        $env = $this->detectEnvironment(fn() => $app->config('app.env', 'production'));
        $app['env'] = $env;
        $app->config()->set('app.env', $env);
    }
    
    protected function detectWebEnvironment(Closure $callback) :string
    {
        return $callback();
    }
    
    protected function detectConsoleEnvironment(Closure $callback, array $args) :string
    {
        // First we will check if an environment argument was passed via console arguments
        // and if it was that automatically overrides as the environment. Otherwise, we
        // will check the environment as a "web" request like a typical HTTP request.
        if ( ! is_null($value = $this->parseEnvironmentArgument($args))) {
            return $value;
        }
        
        return $this->detectWebEnvironment($callback);
    }
    
    protected function parseEnvironmentArgument(array $args) :?string
    {
        foreach ($args as $i => $value) {
            if ($value === '--env') {
                return $args[$i + 1] ?? null;
            }
            
            if (Str::startsWith($value, '--env')) {
                return head(array_slice(explode('=', $value), 1));
            }
        }
        
        return null;
    }
    
    private function detectEnvironment(Closure $default) :string
    {
        $consoleArgs = $_SERVER['argv'] ?? null;
        if ($consoleArgs) {
            return $this->detectConsoleEnvironment($default, $consoleArgs);
        }
        
        return $this->detectWebEnvironment($default);
    }
    
}