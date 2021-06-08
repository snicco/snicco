<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Middleware\Core\ShareCookies;

    class ShareCookiesTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;

        /**
         *
         * @var Request
         */
        private $request;

        /** @var Cookies */
        private $cookies;

        protected function beforeTestRun()
        {

            $this->cookies = new Cookies();

            $this->request = TestRequest::from('GET', '/foo');

        }

        private function newMiddleware() : ShareCookies
        {

            $middleware = new ShareCookies($this->cookies);

            return $middleware;

        }

        /** @test */
        public function cookies_sent_by_the_browser_are_shared()
        {

            $request = $this->request->withAddedHeader('Cookie', 'foo=bar');


            $this->newMiddleware()
                 ->handle($request, new Delegate(function (Request $request) {

                     $this->assertTrue($request->getCookies()->has('foo'));

                     return $this->createResponseFactory()->createResponse();

                 }

                 ));


        }

        /** @test */
        public function added_cookies_are_transformed_to_a_http_header()
        {


            $this->assertEmpty($this->cookies->toHeaders());

            $response = $this->newMiddleware()
                             ->handle($this->request, new Delegate(function (Request $request) {

                                 $this->cookies->set('foo', 'bar');

                                 return $this->createResponseFactory()->createResponse();

                             }

                             ));

            $cookie_header = $response->getHeaderLine('Set-Cookie');

            $this->assertSame('foo=bar', $cookie_header);

        }

    }



