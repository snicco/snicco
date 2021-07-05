<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use BetterWP\Http\Cookie;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\ResponseEmitter;
    use BetterWP\Middleware\Core\ShareCookies;

    class ShareCookiesTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         *
         * @var Request
         */
        private $request;

        protected function beforeTestRun()
        {

            $this->request = TestRequest::from('GET', '/foo');

        }

        private function newMiddleware() : ShareCookies
        {

            return new ShareCookies();

        }

        /** @test */
        public function cookies_sent_by_the_browser_are_shared()
        {

            $request = $this->request->withAddedHeader('Cookie', 'foo=bar');


            $this->newMiddleware()
                 ->handle($request, new Delegate(function (Request $request) {

                     $this->assertTrue($request->cookies()->has('foo'));

                     return $this->createResponseFactory()->createResponse();

                 }

                 ));


        }

        /** @test */
        public function response_cookies_can_be_added () {


            $response = $this->newMiddleware()
                             ->handle($this->request, new Delegate(function () {

                                 $response = $this->createResponseFactory()->make();

                                 $cookie = new Cookie('foo', 'bar');

                                 return $response->withCookie( $cookie );

                             }

                             ));

            $cookie_header = $response->getHeaderLine('Set-Cookie');

            $this->assertSame('foo=bar; path=/; secure; HostOnly; HttpOnly; SameSite=Lax', $cookie_header);

        }

        /** @test */
        public function multiple_cookies_can_be_added () {

            $response = $this->newMiddleware()
                             ->handle($this->request, new Delegate(function () {

                                 $response = $this->createResponseFactory()->make();

                                 $cookie1 = new Cookie('foo', 'bar');
                                 $cookie2 = new Cookie('baz', 'biz');

                                 return $response->withCookie( $cookie1 )
                                                 ->withCookie($cookie2);

                             }

                             ));

            $cookie_header = $response->getHeader('Set-Cookie');

            $this->assertSame('foo=bar; path=/; secure; HostOnly; HttpOnly; SameSite=Lax', $cookie_header[0]);
            $this->assertSame('baz=biz; path=/; secure; HostOnly; HttpOnly; SameSite=Lax', $cookie_header[1]);

        }

        /** @test */
        public function a_cookie_can_be_deleted () {


            $response = $this->newMiddleware()
                             ->handle($this->request, new Delegate(function () {

                                 $response = $this->createResponseFactory()->make();

                                 return $response->withoutCookie( 'foo' );

                             }

                             ));

            $cookie_header = $response->getHeader('Set-Cookie');
            $this->assertSame("foo=deleted; path=/; expires=Thu, 01-Jan-1970 00:00:01 UTC; secure; HostOnly; HttpOnly; SameSite=Lax", $cookie_header[0]);

        }


    }



