<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Psr\Http\Message\ServerRequestInterface;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Encryptor;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Routing\Conditions\AdminAjaxCondition;
    use WPEmerge\Routing\Conditions\AdminPageCondition;
    use WPEmerge\Routing\Conditions\QueryStringCondition;
    use WPEmerge\Routing\Conditions\RequestAttributeCondition;
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
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\FilePath;

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

            $this->bindConfig();

            $this->bindRouteMatcher();

            $this->bindRouteCollection();

            $this->bindRouter();

            $this->bindRouteUrlGenerator();

            $this->bindUrlGenerator();


        }

        public function bootstrap() : void
        {

            $router = $this->container->make(Router::class);

            (new RouteRegistrar($router, $this->config))->loadRoutes();

        }

        private function checkIfValidCacheDir( $dir ) {

            if ( ! $dir || ! is_dir( $dir )) {

                throw new ConfigurationException("No valid cache dir provided:{$dir}");

            }

        }

        private function bindConfig() : void
        {

            $this->config->extend('routing.conditions', self::CONDITION_TYPES);
            $this->config->extend('routing.must_match_web_routes', false);

        }

        private function bindRouteMatcher() : void
        {

            $this->container->singleton(RouteMatcher::class, function () {


                if ( ! $this->config->get('routing.cache', false)) {

                    return new FastRouteMatcher();

                }

                $cache_dir = $this->config->get('routing.cache_dir', '');

                $this->checkIfValidCacheDir($cache_dir);

                return new CachedFastRouteMatcher(
                    new FastRouteMatcher(),
                    FilePath::addTrailingSlash($cache_dir).'__generated_route_map'
                );


            });
        }

        private function bindRouteCollection() : void
        {

            $this->container->singleton(AbstractRouteCollection::class, function () {


                if ( ! $this->config->get('routing.cache', false)) {

                    return new RouteCollection(
                        $this->container->make(RouteMatcher::class),
                        $this->container->make(ConditionFactory::class),
                        $this->container->make(RouteActionFactory::class)
                    );

                }

                $cache_dir = $this->config->get('routing.cache_dir', '');

                $this->checkIfValidCacheDir($cache_dir);

                return new CachedRouteCollection(
                    $this->container->make(RouteMatcher::class),
                    $this->container->make(ConditionFactory::class),
                    $this->container->make(RouteActionFactory::class),
                    FilePath::addTrailingSlash($cache_dir).'__generated_route_collection',
                );


            });
        }

        private function bindRouter() : void
        {

            $this->container->singleton(Router::class, function () {

                return new Router(
                    $this->container,
                    $this->container->make(AbstractRouteCollection::class),

                );
            });
        }

        private function bindRouteUrlGenerator() : void
        {

            $this->container->singleton(RouteUrlGenerator::class, function () {

                return new FastRouteUrlGenerator($this->container->make(
                    AbstractRouteCollection::class
                )
                );

            });
        }

        private function bindUrlGenerator() : void
        {

            $this->container->singleton(UrlGenerator::class, function () {

                $generator = new UrlGenerator($this->container->make(RouteUrlGenerator::class));

                $generator->setRequestResolver( function () {

                    return $this->container->make(ServerRequestInterface::class);

                });

                $generator->setAppKeyResolver(function () {
                    $key = $this->config->get('app_key',  '');

                    Encryptor::validAppKey($key);

                    return $key;
                });

               $generator->setSessionResolver(function () {

                   if ( ! $this->container->offsetExists(SessionStore::class ) ) {

                       throw new ConfigurationException(
                           'You cant use UrlGeneration functions that depend on sessions without using the session extension.'
                       );

                   }

                   return $this->container->make(SessionStore::class);


               });

               return $generator;


            });
        }


    }
