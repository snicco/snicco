<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Tests\stubs\TestMagicLink;
    use Tests\stubs\TestRequest;
    use BetterWP\Contracts\MagicLink;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\FastRoute\FastRouteUrlGenerator;
    use BetterWP\Routing\UrlGenerator;

    trait CreateUrlGenerator
    {

        /** @var MagicLink */
        protected $magic_link;

        /** @var UrlGenerator */
        protected $generator;

        protected function newUrlGenerator(string $app_key = null, Request $request = null) : UrlGenerator
        {

            $routes = $this->routes ?? $this->newRouteCollection();

            $this->routes = $routes;

            if (!isset($this->routes ) ) {
                $this->routes = $routes;
            }

            $magic_link = new TestMagicLink();

            $this->magic_link = $magic_link;

            $generator = new UrlGenerator(new FastRouteUrlGenerator($this->routes), $magic_link);

            $this->generator = $generator;

            $generator->setRequestResolver(function () use ($request ){

                return $request ?? TestRequest::fromFullUrl('GET', SITE_URL);

            });


            return $generator;

        }

    }