<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\stubs\TestRequest;
    use Tests\traits\AssertsResponse;
    use Tests\traits\SetUpRouter;
    use Tests\UnitTest;
    use WPEmerge\Facade\WP;

    class RouteRegistrationTest extends UnitTest
    {

        use SetUpRouter;
        use AssertsResponse;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {

            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();
        }

        /** @test */
        public function routes_can_be_defined_without_leading_slash()
        {

            $this->router->get('foo', function () {

                return 'FOO';

            });

            $this->router->loadRoutes();

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->assertOutput('FOO', $this->router->runRoute($request));

        }

        /** @test */
        public function routes_can_be_defined_with_leading_slash()
        {

            $this->router->get('/foo', function () {

                return 'FOO';

            });

            $this->router->loadRoutes();

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->assertOutput('FOO', $this->router->runRoute($request));

        }

        /** @test */
        public function routes_without_trailing_slash_dont_match_request_with_trailing_slash()
        {

            $this->router->get('/foo', function () {

                return 'FOO';

            });

            $this->router->loadRoutes();

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->assertOutput('FOO', $this->router->runRoute($request));

        }

        /** @test */
        public function routes_with_trailing_slash_match_request_with_trailing_slash()
        {

            $this->router->get('/foo/', function () {

                return 'FOO';

            });

            $this->router->loadRoutes();

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->assertOutput('FOO', $this->router->runRoute($request));


            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->assertNullResponse($this->router->runRoute($request));

        }



    }