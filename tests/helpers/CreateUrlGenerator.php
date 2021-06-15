<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Tests\stubs\TestRequest;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\ArrayMagicLink;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\UrlGenerator;

    trait CreateUrlGenerator
    {

        protected function newUrlGenerator(string $app_key = null, Request $request = null) : UrlGenerator
        {

            $routes = $this->routes ?? $this->newRouteCollection();

            if (! isset($this->routes ) ) {
                $this->routes = $routes;
            }

            $generator = new UrlGenerator(new FastRouteUrlGenerator($this->routes), new ArrayMagicLink());

            $generator->setRequestResolver(function () use ($request ){

                return $request ?? TestRequest::fromFullUrl('GET', SITE_URL);

            });

            if ( $app_key ) {

                $generator->setAppKeyResolver(function () use ($app_key) {
                    return $app_key;
                });

            }

            return $generator;

        }

    }