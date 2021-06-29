<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Carbon\Carbon;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\ExceptionHandling\Exceptions\InvalidSignatureException;
    use WPEmerge\Testing\TestingErrorHandler;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Middleware\Core\ShareCookies;
    use WPEmerge\Middleware\ValidateSignature;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Session\Drivers\ArraySessionDriver;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    class ValidateSignatureTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;
        use AssertsResponse;
        use CreateDefaultWpApiMocks;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var Delegate
         */
        private $delegate;


        protected function beforeTestRun()
        {

            $this->response_factory = $this->createResponseFactory();
            $this->delegate = new Delegate(function () {

                return $this->response_factory->make(200);
            });
        }

        private function newMiddleware(MagicLink $magic_link, string $type = 'relative') : ValidateSignature
        {

            $m = new ValidateSignature($magic_link, $type);
            $m->setResponseFactory($this->response_factory);

            return $m;


        }

        /** @test */
        public function a_valid_signature_grants_access_to_the_route()
        {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url);

            $m = $this->newMiddleware($this->magic_link);

            $response = $m->handle($request, $this->delegate);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function signatures_that_were_created_from_absolute_urls_can_be_validated () {

            $url = $this->generator->signed('foo', 300, true);
            $request = TestRequest::fromFullUrl('GET', $url);

            $m = $this->newMiddleware($this->magic_link, 'absolute');

            $response = $m->handle($request, $this->delegate);

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function an_exception_is_thrown_for_invalid_signatures () {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url . 'changed');

            $m = $this->newMiddleware($this->magic_link);

            $this->expectException(InvalidSignatureException::class);

            $response = $m->handle($request, $this->delegate);



        }

        /** @test */
        public function the_magic_links_is_invalidated_after_the_first_access()
        {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url);

            $this->assertArrayHasKey($request->query('signature'), $this->magic_link->getStored());

            $m = $this->newMiddleware($this->magic_link);

            $response = $m->handle($request, $this->delegate);

            $this->assertArrayNotHasKey($request->query('signature'), $this->magic_link->getStored());

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function if_sessions_are_used_the_user_with_the_session_can_access_the_route_several_times()
        {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url)
                                  ->withSession($session = new Session(new ArraySessionDriver(10)));

            $m = $this->newMiddleware($this->magic_link);
            $response = $m->handle($request, $this->delegate);
            $this->assertStatusCode(200, $response);

            $response = $m->handle($request, $this->delegate);
            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function session_based_access_is_not_possible_after_expiration_time () {

            $url = $this->generator->signed('foo', 500);
            $request = TestRequest::from('GET', $url)
                                  ->withSession($session = new Session(new ArraySessionDriver(10)));

            $m = $this->newMiddleware($this->magic_link);
            $response = $m->handle($request, $this->delegate);
            $this->assertStatusCode(200, $response);

            $this->expectException(InvalidSignatureException::class);

            Carbon::setTestNow(Carbon::now()->addSeconds(501));

            $m->handle($request, $this->delegate);

            Carbon::setTestNow();



        }

        /** @test */
        public function if_sessions_are_not_used_a_cookie_is_used_to_allow_access_to_the_route()
        {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url);
            $c = $this->createContainer();
            $c->instance(ResponseFactory::class, $this->response_factory);

            $pipeline = new Pipeline($c, new TestingErrorHandler());
            $m = $this->newMiddleware($this->magic_link);

            $response = $pipeline->send($request)
                                 ->through([ShareCookies::class, $m])
                                 ->then(function () {

                                     return $this->response_factory->make();
                                 });


            $cookies = $response->getHeaderLine('Set-Cookie');
            $cookie = collect(explode(';', $cookies))->flatMap(function ($value)  {

                return [trim(Str::before($value, '=')) => trim(Str::after($value, '='))];

            })->all();

            $this->assertSame('/foo', $cookie['path']);
            $this->assertSame('secure', $cookie['secure']);
            $this->assertSame('HostOnly', $cookie['HostOnly']);
            $this->assertSame('HttpOnly', $cookie['HttpOnly']);
            $this->assertSame('Lax', $cookie['SameSite']);


            $c = Arr::firstKey($cookie) . '=' . Arr::firstEl($cookie) .';';
            $request_with_access_cookie = $request->withAddedHeader('Cookie', $c);

            $response = $pipeline->send($request_with_access_cookie)
                                 ->through([ShareCookies::class, $m])
                                 ->then(function () {

                                     return $this->response_factory->make();
                                 });

            $this->assertStatusCode(200, $response);

        }

        /** @test */
        public function cookie_based_route_access_is_not_possible_after_the_expiration_time () {

            $url = $this->generator->signed('foo', 500);
            $request = TestRequest::from('GET', $url);
            $c = $this->createContainer();
            $c->instance(ResponseFactory::class, $this->response_factory);

            $pipeline = new Pipeline($c, new TestingErrorHandler());
            $m = $this->newMiddleware($this->magic_link);

            $response = $pipeline->send($request)
                                 ->through([ShareCookies::class, $m])
                                 ->then(function () {

                                     return $this->response_factory->make();
                                 });


            $cookie = $response->getHeaderLine('Set-Cookie');

            $request_with_access_cookie = $request->withAddedHeader('Cookie', $cookie);

            $this->expectException(InvalidSignatureException::class);

            Carbon::setTestNow(Carbon::now()->addSeconds(501));

            $pipeline->send($request_with_access_cookie)
                                 ->through([ShareCookies::class, $m])
                                 ->then(function () {

                                     return $this->response_factory->make();
                                 });

            Carbon::setTestNow();


        }

        /** @test */
        public function the_same_user_can_access_the_route_multiple_times()
        {

            $url = $this->generator->signed('foo');
            $request = TestRequest::from('GET', $url);
            $m = $this->newMiddleware($this->magic_link);
            $response = $m->handle($request, $this->delegate);
            $this->assertStatusCode(200, $response);

        }

    }
