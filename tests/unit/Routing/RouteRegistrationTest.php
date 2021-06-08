<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Conditions\QueryStringCondition;
    use WPEmerge\Routing\Router;

    class RouteRegistrationTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function routes_can_be_defined_without_leading_slash()
        {

            $this->createRoutes(function () {

                $this->router->get('foo', function () {

                    return 'FOO';

                });


            });

            $request = new IncomingWebRequest(TestRequest::fromFullUrl('GET', 'https://foobar.com/foo'), 'wp.php' );
            $this->runAndAssertOutput('FOO', $request);

        }

        /** @test */
        public function routes_can_be_defined_with_leading_slash()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'FOO';

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

        }

        /** @test */
        public function routes_without_trailing_slash_dont_match_request_with_trailing_slash()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'FOO';

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->runAndAssertEmptyOutput(new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

        }

        /** @test */
        public function routes_with_trailing_slash_match_request_with_trailing_slash()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo/', function () {

                    return 'FOO';

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->runAndAssertEmptyOutput(new IncomingWebRequest($request, 'wp.php'));

        }

        /** @test */
        public function routes_with_trailing_slash_match_request_with_trailing_slash_when_inside_a_group()
        {

            $this->createRoutes(function () {

                $this->router->name('foo')->group(function () {

                    $this->router->get('/foo/', function () {

                        return 'FOO';

                    });

                    $this->router->prefix('bar')->group(function () {

                        $this->router->post('/foo/', function () {

                            return 'FOO';

                        });

                    });

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
            $this->runAndAssertEmptyOutput(new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('POST', 'https://foobar.com/bar/foo/');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('POST', 'https://foobar.com/bar/foo');
            $this->runAndAssertEmptyOutput(new IncomingWebRequest($request, 'wp.php'));


        }

        /** @test */
        public function url_encoded_routes_work()
        {

            $this->createRoutes(function () {

                $this->router->get('/german-city/{city}', function (Request $request, string $city) {

                    return ucfirst($city);

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/german-city/münchen');
            $this->runAndAssertOutput('München', new IncomingWebRequest($request, 'wp.php'));

        }

        /** @test */
        public function url_encoded_query_string_conditions_work () {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'FOO';

                })->where(QueryStringCondition::class, ['page' => 'bayern münchen']);

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo?page=bayern münchen');
            $request = $request->withQueryParams(['page'=>'bayern münchen']);
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

        }

    }