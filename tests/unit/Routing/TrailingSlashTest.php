<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\UnitTest;
    use BetterWP\Application\ApplicationEvent;
    use BetterWP\Events\IncomingWebRequest;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\Conditions\QueryStringCondition;
    use BetterWP\Routing\Router;

    class TrailingSlashTest extends UnitTest
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

            $request = new IncomingWebRequest(TestRequest::fromFullUrl('GET', 'https://foobar.com/foo'), 'wp.php');
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
        public function routes_with_segments_can_only_match_trailing_slashes () {

            $this->createRoutes(function () {

                $this->router->get('/foo/{bar}/', function (string $bar) {

                    return strtoupper($bar);

                });

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/bar/');
            $this->runAndAssertOutput('BAR', new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/bar');
            $this->runAndAssertEmptyOutput(new IncomingWebRequest($request, 'wp.php'));

        }

        /** @test */
        public function the_router_can_be_forced_to_always_add_trailing_slashes_to_routes () {


            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'FOO';

                });

            }, true );

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo/');
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo');
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
        public function url_encoded_query_string_conditions_work()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function () {

                    return 'FOO';

                })->where(QueryStringCondition::class, ['page' => 'bayern münchen']);

            });

            $request = TestRequest::fromFullUrl('GET', 'https://foobar.com/foo?page=bayern münchen');
            $request = $request->withQueryParams(['page' => 'bayern münchen']);
            $this->runAndAssertOutput('FOO', new IncomingWebRequest($request, 'wp.php'));

        }

    }