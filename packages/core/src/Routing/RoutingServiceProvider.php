<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Support\WP;
use Snicco\Support\FilePath;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\MagicLink;
use Snicco\Http\DatabaseMagicLink;
use Snicco\Contracts\RouteRegistrar;
use Snicco\Contracts\RouteUrlMatcher;
use Snicco\Contracts\ServiceProvider;
use Snicco\Contracts\RouteUrlGenerator;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Snicco\Contracts\RouteCollectionInterface;
use Snicco\Routing\Conditions\CustomCondition;
use Snicco\Routing\Conditions\NegateCondition;
use Snicco\Routing\Conditions\PostIdCondition;
use Snicco\Routing\Conditions\PostSlugCondition;
use Snicco\Routing\Conditions\PostTypeCondition;
use Snicco\Routing\FastRoute\FastRouteUrlMatcher;
use Snicco\Routing\Conditions\PostStatusCondition;
use Snicco\Routing\Conditions\QueryStringCondition;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Snicco\Routing\Conditions\PostTemplateCondition;

class RoutingServiceProvider extends ServiceProvider
{
    
    /**
     * @var array<string, string>
     */
    public const CONDITION_TYPES = [
        'custom' => CustomCondition::class,
        'negate' => NegateCondition::class,
        'post_id' => PostIdCondition::class,
        'post_slug' => PostSlugCondition::class,
        'post_status' => PostStatusCondition::class,
        'post_template' => PostTemplateCondition::class,
        'post_type' => PostTypeCondition::class,
        'query_string' => QueryStringCondition::class,
    ];
    
    public function register() :void
    {
        $this->bindConfig();
        
        $this->extendRoutes($this->config->get('app.package_root').DIRECTORY_SEPARATOR.'routes');
        
        $this->bindRouteMatcher();
        
        $this->bindRouteCollection();
        
        $this->bindRouter();
        
        $this->bindRouteUrlGenerator();
        
        $this->bindMagicLink();
        
        $this->bindUrlGenerator();
        
        $this->bindRouteRegistrar();
    }
    
    public function bootstrap() :void
    {
        $this->bindRoutePresets();
        $this->loadRoutes();
    }
    
    private function bindConfig() :void
    {
        $this->config->extend('routing.conditions', self::CONDITION_TYPES);
        $this->config->extend('routing.must_match_web_routes', false);
        $this->config->extend('routing.api.endpoints', []);
        $this->config->extend('routing.cache', false);
        $this->config->extend(
            'routing.cache_dir',
            $this->config->get('app.storage_dir')
            .DIRECTORY_SEPARATOR
            .'framework'
            .DIRECTORY_SEPARATOR
            .'routes'
        );
    }
    
    private function bindRouteMatcher() :void
    {
        $this->container->singleton(RouteUrlMatcher::class, FastRouteUrlMatcher::class);
    }
    
    private function bindRouteCollection() :void
    {
        $this->container->singleton(RouteCollectionInterface::class, function () {
            if ( ! $this->config->get('routing.cache', false)) {
                return new RouteCollection(
                    $this->container->make(RouteUrlMatcher::class),
                    null
                );
            }
            
            $cache_dir = $this->config->get('routing.cache_dir', '');
            
            return new RouteCollection(
                $this->container->make(RouteUrlMatcher::class),
                FilePath::addTrailingSlash($cache_dir).'__generated:snicco_wp_route_collection',
            );
        });
    }
    
    private function bindRouter() :void
    {
        $this->container->singleton(Router::class, function () {
            return new Router(
                $this->container->make(RouteCollectionInterface::class),
                $this->withSlashes()
            );
        });
    }
    
    private function bindRouteUrlGenerator() :void
    {
        $this->container->singleton(RouteUrlGenerator::class, FastRouteUrlGenerator::class);
    }
    
    private function bindMagicLink()
    {
        $this->container->singleton(MagicLink::class, function () {
            if ($this->app->isRunningUnitTest()) {
                return new TestMagicLink();
            }
            
            $magic_link = new DatabaseMagicLink('magic_links');
            $magic_link->setAppKey($this->appKey());
            
            return $magic_link;
        });
    }
    
    private function bindUrlGenerator() :void
    {
        $this->container->singleton(UrlGenerator::class, function () {
            $generator = new UrlGenerator(
                $this->container->make(RouteUrlGenerator::class),
                $this->container->make(MagicLink::class),
                $this->withSlashes(),
            );
            
            $generator->setRequestResolver(fn() => $this->container->make(Request::class));
            
            return $generator;
        });
    }
    
    private function bindRouteRegistrar()
    {
        $this->container->singleton(RouteRegistrar::class, function () {
            $registrar = new RouteFileRegistrar(
                $this->container->make(Router::class),
            );
            
            if ( ! $this->config->get('routing.cache', false)) {
                return $registrar;
            }
            
            return new CachedRouteFileRegistrar($registrar);
        });
    }
    
    private function loadRoutes()
    {
        /** @var RouteFileRegistrar $registrar */
        $registrar = $this->app->resolve(RouteRegistrar::class);
        $registrar->registerRoutes($this->config);
        $this->app[Router::class]->loadRoutes();
    }
    
    /**
     * @note
     * This need to run inside a bootstrap method so that other providers
     * get the chance to register their own api endpoints in their respective register() method.
     */
    private function bindRoutePresets()
    {
        $this->config->extend('routing.presets.web', [
            'middleware' => ['web'],
        ]);
        
        $this->config->extend('routing.presets.admin', [
            'middleware' => ['admin'],
            'prefix' => WP::wpAdminFolder(),
            'name' => 'admin',
        ]);
        
        $this->config->extend('routing.presets.ajax', [
            'middleware' => ['ajax'],
            'prefix' => WP::wpAdminFolder().DIRECTORY_SEPARATOR.'admin-ajax.php',
            'name' => 'ajax',
        ]);
        
        foreach ($this->config->get('routing.api.endpoints', []) as $name => $prefix) {
            $this->config->extend(
                'routing.presets.'.$name,
                [
                    'prefix' => $prefix,
                    'name' => $name,
                    'middleware' => ['api'],
                ]
            );
        }
    }
    
}
