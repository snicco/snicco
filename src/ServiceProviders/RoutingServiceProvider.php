<?php


    declare(strict_types = 1);


    namespace WPEmerge\ServiceProviders;

    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Factories\HandlerFactory;
    use WPEmerge\Routing\Conditions\AdminAjaxCondition;
    use WPEmerge\Routing\Conditions\AdminPageCondition;
    use WPEmerge\Routing\Conditions\QueryStringCondition;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Routing\Conditions\CustomCondition;
    use WPEmerge\Routing\Conditions\NegateCondition;
    use WPEmerge\Routing\Conditions\PostIdCondition;
    use WPEmerge\Routing\Conditions\PostSlugCondition;
    use WPEmerge\Routing\Conditions\PostStatusCondition;
    use WPEmerge\Routing\Conditions\PostTemplateCondition;
    use WPEmerge\Routing\Conditions\PostTypeCondition;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\RouteCompiler;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Routing\UrlGenerator;

    class RoutingServiceProvider extends ServiceProvider
    {


        /**
         * Key=>Class dictionary of condition types
         *
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
            'admin_page' => AdminPageCondition::class,
            'admin_ajax' => AdminAjaxCondition::class,
            'request' => RequestAttributeCondition::class,
        ];


        public function register() : void
        {

            $this->config->extend('routing.conditions', self::CONDITION_TYPES);

            $this->container->singleton(RouteMatcher::class, function () {


                if ( ! $this->config->get('routing.cache', false)) {

                    return new FastRouteMatcher();

                }

                $cache_file = $this->config->get('routing.cache_file', null);

                if ( ! $cache_file) {

                    throw new ConfigurationException("No cache file provided:{$cache_file}");

                }

                /** @todo Named routes will not work right now with caching enabled. */
                /** @todo Need a way to also cache routes outside of the route matcher */
                return new CachedFastRouteMatcher(new FastRouteMatcher(), $cache_file);


            });

            $this->container->singleton(RouteCompiler::class, function () {

                return new RouteCompiler(
                    $this->container->make(HandlerFactory::class),
                    $this->container->make(ConditionFactory::class)
                );

            });

            $this->container->singleton(RouteCollection::class, function () {

                return new RouteCollection(
                    $this->container->make(RouteMatcher::class),
                    $this->container->make(RouteCompiler::class)
                );

            });

            $this->container->singleton(Router::class, function () {

                return new Router(
                    $this->container,
                    $this->container->make(RouteCollection::class),
                    $this->container->make(ResponseFactory::class)

                );
            });

            $this->container->singleton(RouteUrlGenerator::class, function () {

                return new FastRouteUrlGenerator($this->container->make(
                    RouteCollection::class)
                );

            });

            $this->container->singleton(UrlGenerator::class, function () {

                return new UrlGenerator($this->container->make(RouteUrlGenerator::class));

            });

        }


        public function bootstrap() : void
        {

            $router = $this->container->make(Router::class);

            (new RouteRegistrar($router, $this->config))->loadRoutes();

        }

    }
