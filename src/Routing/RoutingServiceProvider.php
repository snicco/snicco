<?php


    declare(strict_types = 1);


    namespace WPEmerge\Routing;

    use Symfony\Component\Finder\Finder;
    use Tests\stubs\TestMagicLink;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Http\DatabaseMagicLink;
    use WPEmerge\ExceptionHandling\Exceptions\ConfigurationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\Psr7\Request;
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
    use WPEmerge\Session\Session;
    use WPEmerge\Support\FilePath;


    class RoutingServiceProvider extends ServiceProvider
    {

        /**
         * Alias=>Class dictionary of condition types
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
            'request' => RequestAttributeCondition::class,

            // These two are only used to the url generation functionality
            'admin_page' => AdminPageCondition::class,
            'admin_ajax' => AdminAjaxCondition::class,
        ];

        public function register() : void
        {

            $this->bindConfig();

            $this->extendRoutes($this->config->get('app.package_root') . DIRECTORY_SEPARATOR . 'routes');

            $this->bindRouteMatcher();

            $this->bindRouteCollection();

            $this->bindRouter();

            $this->bindRouteUrlGenerator();

            $this->bindMagicLink();

            $this->bindUrlGenerator();

            $this->bindRouteRegistrar();

        }

        /**
         * @throws ConfigurationException
         */
        public function bootstrap() : void
        {

            $endpoints = $this->config->get('routing.api.endpoints');

            foreach ($endpoints as $id => $prefix) {

                $name = 'api.'.$id;

                $this->config->extend(
                    'routing.presets.'.$name,
                    [
                        'prefix' => $prefix,
                        'name' => $id,
                        'middleware' => [$name]
                    ]
                );

                $this->config->extend('middleware.groups', [

                    $name => []

                ]);

            }

        }

        private function bindConfig() : void
        {

            $this->config->extend('routing.conditions', self::CONDITION_TYPES);
            $this->config->extend('routing.must_match_web_routes', false);
            $this->config->extend('routing.api.endpoints', []);
            $this->config->extend('routing.cache', ! $this->config->get('app.debug') );
            $this->config->extend(
                'routing.cache_dir',
                $this->config->get('app.storage_dir'). DIRECTORY_SEPARATOR . 'framework'. DIRECTORY_SEPARATOR . 'routes'
            );

        }

        private function bindRouteMatcher() : void
        {

            $this->container->singleton(RouteMatcher::class, function () {


                if ( ! $this->config->get('routing.cache', false)) {

                    return new FastRouteMatcher();

                }

                $cache_dir = $this->config->get('routing.cache_dir', '');

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
                    $this->withSlashes()
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


                $generator = new UrlGenerator(
                    $this->container->make(RouteUrlGenerator::class),
                    $this->container->make(MagicLink::class),
                    $this->withSlashes(),
                );

                $generator->setRequestResolver(function () {

                    return $this->container->make(Request::class);

                });


                return $generator;


            });
        }

        private function bindRouteRegistrar()
        {

            $this->container->singleton(RouteRegistrarInterface::class, function () {


                $registrar =  new RouteRegistrar(
                    $this->container->make(Router::class),
                );

                if ( ! $this->config->get('routing.cache', false)) {

                    return $registrar;


                }

                return new CacheFileRouteRegistrar($registrar);


            });
        }

        private function bindMagicLink()
        {

            $this->container->singleton(MagicLink::class, function () {

                if ( $this->app->isRunningUnitTest() ) {
                    return new TestMagicLink();
                }

                $magic_link = new DatabaseMagicLink('magic_links' );
                $magic_link->setAppKey($this->appKey());

                return $magic_link;

            });
        }

    }
