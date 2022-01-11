<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Closure;
use LogicException;
use Snicco\Support\Str;
use Webmozart\Assert\Assert;
use InvalidArgumentException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class PHPFileRouteLoader
{
    
    public const VERSION_FLAG = '-v';
    
    public const WEB_ROUTES_NAME = 'web';
    
    public const ADMIN_ROUTES_NAME = 'admin';
    
    // Match all files that end with ".php" and don't start with an underscore.
    // https://regexr.com/691di
    private const SEARCH_PATTERN = '/^[^_].+\.php$/';
    
    private RoutingConfigurator $routing_configurator;
    
    private RouteLoadingOptions $options;
    
    public function __construct(RoutingConfigurator $routing_configurator, RouteLoadingOptions $options)
    {
        Assert::isInstanceOf($routing_configurator, WebRoutingConfigurator::class);
        Assert::isInstanceOf($routing_configurator, AdminRoutingConfigurator::class);
        $this->routing_configurator = $routing_configurator;
        $this->options = $options;
    }
    
    public function loadRoutesIn(array $route_directories) :void
    {
        foreach ($this->getFiles($route_directories) as $file) {
            $name = $file->getFilenameWithoutExtension();
            
            $attributes = $this->options->getRouteAttributes($name);
            
            $this->requireFile($file, $attributes);
        }
    }
    
    public function loadApiRoutesIn(array $api_directories) :void
    {
        foreach ($this->getFiles($api_directories) as $file) {
            $name = $file->getFilenameWithoutExtension();
            
            [$name, $version] = $this->parseNameAndVersion($name);
            
            $attributes = $this->options->getApiRouteAttributes($name, $version);
            
            $this->requireFile($file, $attributes);
        }
    }
    
    private function requireFile(SplFileInfo $file, array $attributes = []) :void
    {
        $this->validateAttributes($attributes);
        
        if ( ! $file->isReadable()) {
            throw new LogicException(
                "Route file [{$file->getRealPath()}] is not readable."
            );
        }
        
        $closure = require $file;
        
        Assert::isInstanceOf(
            $closure,
            Closure::class,
            "Route file [{$file->getRealPath()}] did not return a closure."
        );
        
        $this->routing_configurator->group(
            $closure,
            $attributes
        );
    }
    
    private function getFiles(array $route_directories) :Finder
    {
        Assert::allString($route_directories);
        Assert::allReadable($route_directories);
        
        $finder = new Finder();
        $finder->in($route_directories)
               ->depth(0)
               ->files()
               ->name(self::SEARCH_PATTERN);
        
        return $finder;
    }
    
    /**
     * @return array First value is the filename, second value is the optional version
     */
    private function parseNameAndVersion(string $filename) :array
    {
        // https://regexr.com/6d3v2
        $pattern = '/^(?:\w+'.self::VERSION_FLAG.')(\d+)$/';
        
        $res = preg_match($pattern, $filename, $match);
        
        if (1 === $res) {
            Assert::keyExists($match, 1);
            return [Str::before($filename, self::VERSION_FLAG), $match[1]];
        }
        return [$filename, null];
    }
    
    private function validateAttributes(array $attributes) :void
    {
        foreach ($attributes as $key => $value) {
            switch ($key) {
                case RoutingConfigurator::MIDDLEWARE_KEY:
                    Assert::isArray(
                        $value,
                        'Middleware for api options has to be an array of strings.'
                    );
                    Assert::allString(
                        $value,
                        'Middleware for api options has to be an array of strings.'
                    );
                    break;
                case RoutingConfigurator::PREFIX_KEY:
                    Assert::startsWith(
                        $value,
                        '/',
                        sprintf(
                            '[%s] has to be a string that starts with a forward slash.',
                            RoutingConfigurator::PREFIX_KEY
                        )
                    );
                    break;
                case RoutingConfigurator::NAMESPACE_KEY:
                    Assert::stringNotEmpty(
                        $value,
                        sprintf(
                            '[%s] has to be a non-empty string.',
                            RoutingConfigurator::NAMESPACE_KEY
                        )
                    );
                    break;
                case RoutingConfigurator::NAME_KEY:
                    Assert::stringNotEmpty(
                        $value,
                        sprintf(
                            '[%s] has to be a non-empty string.',
                            RoutingConfigurator::NAME_KEY
                        )
                    );
                    break;
                default;
                    throw new InvalidArgumentException("The option [$key] is not supported.");
            }
        }
    }
    
}