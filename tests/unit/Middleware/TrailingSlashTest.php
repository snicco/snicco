<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Middleware\TrailingSlash;

    class TrailingSlashTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;
        use AssertsResponse;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var Delegate
         */
        private $delegate;

        private function newMiddleware($trailing_slash) : TrailingSlash
        {

            $this->response_factory = $this->createResponseFactory();

            $this->delegate = new Delegate(function () {
                return $this->response_factory->make(200);
            });

            return new TrailingSlash($this->response_factory, $trailing_slash);

        }

        public function testRedirectNoSlashToTrailingSlash () {

            $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar');

            $response = $this->newMiddleware(true)->handle($request, $this->delegate);

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', '/bar/', $response);
            $this->assertStatusCode(301, $response);

        }

        /** @test */
        public function testRedirectSlashToNoSlash () {

            $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar/');

            $response = $this->newMiddleware(false)->handle($request, $this->delegate);

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', '/bar', $response);
            $this->assertStatusCode(301, $response);

        }

        public function testNoRedirectIfMatches () {

            $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar');
            $response = $this->newMiddleware(false )->handle($request, $this->delegate);
            $this->assertNotInstanceOf(RedirectResponse::class, $response);
            $this->assertStatusCode(200, $response);

            $request = TestRequest::fromFullUrl('GET', 'https://foo.com/bar/');
            $response = $this->newMiddleware(true )->handle($request, $this->delegate);
            $this->assertNotInstanceOf(RedirectResponse::class, $response);
            $this->assertStatusCode(200, $response);

        }

    }
