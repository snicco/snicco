<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Support\WP;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\MagicLink;
use Snicco\Core\Http\DatabaseMagicLink;
use Snicco\Core\Contracts\RouteRegistrar;
use Snicco\Core\Contracts\RouteUrlMatcher;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Contracts\ExceptionHandler;
use Snicco\Core\Contracts\RouteUrlGenerator;
use Snicco\Core\Factories\MiddlewareFactory;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Snicco\Core\Contracts\RouteCollectionInterface;
use Snicco\Core\Routing\Conditions\CustomCondition;
use Snicco\Core\Routing\Conditions\NegateCondition;
use Snicco\Core\Routing\Conditions\PostIdCondition;
use Snicco\Core\Routing\Conditions\PostSlugCondition;
use Snicco\Core\Routing\Conditions\PostTypeCondition;
use Snicco\Core\Routing\FastRoute\FastRouteUrlMatcher;
use Snicco\Core\Routing\Conditions\PostStatusCondition;
use Snicco\Core\Routing\Conditions\QueryStringCondition;
use Snicco\Core\Routing\FastRoute\FastRouteUrlGenerator;
use Snicco\Core\Routing\Conditions\PostTemplateCondition;

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
        
        $this->bindRoutingPipeline();
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
        $this->container->singleton(RouteUrlMatcher::class, function () {
            return new FastRouteUrlMatcher();
        });
    }
    
    private function bindRouteCollection() :void
    {
        $this->container->singleton(RouteCollectionInterface::class, function () {
            if ( ! $this->config->get('routing.cache', false)) {
                return new RouteCollection(
                    $this->container->get(RouteUrlMatcher::class),
                    null
                );
            }
            
            $cache_dir = $this->config->get('routing.cache_dir', '');
            
            return new RouteCollection(
                $this->container->get(RouteUrlMatcher::class),
                rtrim($cache_dir, DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .'__generated:snicco_wp_route_collection',
            );
        });
    }
    
    private function bindRouter() :void
    {
        $this->container->singleton(Router::class, function () {
            return new Router(
                $this->container->get(RouteCollectionInterface::class),
                $this->withSlashes()
            );
        });
    }
    
    private function bindRouteUrlGenerator() :void
    {
        $this->container->singleton(RouteUrlGenerator::class, function () {
            return new FastRouteUrlGenerator($this->container[RouteCollectionInterface::class]);
        });
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
                $this->container->get(RouteUrlGenerator::class),
                $this->container->get(MagicLink::class),
                $this->withSlashes(),
            );
            
            $generator->setRequestResolver(fn() => $this->container->get(Request::class));
            
            return $generator;
        });
    }
    
    private function bindRouteRegistrar()
    {
        $this->container->singleton(RouteRegistrar::class, function () {
            $registrar = new RouteFileRegistrar(
                $this->container->get(Router::class),
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
    
    private function bindRoutingPipeline()
    {
        $this->container->factory(Pipeline::class, function () {
            return new Pipeline(
                $this->container[MiddlewareFactory::class],
                $this->container[ExceptionHandler::class],
                $this->container[ResponseFactory::class]
            );
        });
    }
    
}
