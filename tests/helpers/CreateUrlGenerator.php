<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Snicco\Contracts\MagicLink;
    use Snicco\Http\Psr7\Request;
    use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
    use Snicco\Routing\UrlGenerator;
    use Snicco\Testing\TestDoubles\TestMagicLink;
    use Tests\stubs\TestRequest;

    /**
     * @internal
     */
    trait CreateUrlGenerator
    {

        protected MagicLink $magic_link;

        protected UrlGenerator $generator;

        protected function newUrlGenerator(string $app_key = null, Request $request = null, bool $trailing_slash = false) : UrlGenerator
        {

            $routes = $this->routes ?? $this->newRouteCollection();

            $this->routes = $routes;

            if ( ! isset($this->routes)) {
                $this->routes = $routes;
            }

            $magic_link = new TestMagicLink();

            $this->magic_link = $magic_link;

            $generator = new UrlGenerator(new FastRouteUrlGenerator($this->routes), $magic_link, $trailing_slash);

            $this->generator = $generator;

            $generator->setRequestResolver(function () use ($request ){

                return $request ?? TestRequest::fromFullUrl('GET', SITE_URL);

            });


            return $generator;

        }

    }