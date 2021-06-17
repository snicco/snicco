<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request as Request;
    use WPEmerge\Routing\Router;

    class RouteSegmentsTest extends UnitTest
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

        /**
         *
         *
         *
         *
         *
         * ROUTE PARAMETERS, CUSTOM API
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function regex_can_be_added_as_a_condition_without_needing_array_syntax()
        {


            $this->createRoutes(function () {

                $this->router->get('users/{user}', function () {

                    return 'foo';

                })->and('user', '[0-9]+');

            });

            $request = $this->webRequest('GET', '/users/1');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/calvin');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function regex_can_be_added_as_a_condition_as_array_syntax()
        {


            $this->createRoutes(function () {

                $this->router->get('users/{user}', function () {

                    return 'foo';

                })->and(['user', '[0-9]+']);

            });

            $request = $this->webRequest('GET', '/users/1');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/calvin');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function multiple_regex_conditions_can_be_added_to_an_url_condition()
        {

            $this->createRoutes(function () {

                $this->router->get('/user/{id}/{name}', function (Request $request, $id, $name) {

                    return $name.$id;

                })->and(['id' => '[0-9]+', 'name' => '[a-z]+']);

            });

            $request = $this->webRequest('GET', '/user/1/calvin');
            $this->runAndAssertOutput('calvin1', $request);

            $request = $this->webRequest('GET', '/users/1/1');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/users/calvin/calvin');
            $this->runAndAssertEmptyOutput($request);

        }

        /** @test */
        public function optional_parameters_work_at_the_end_of_the_url()
        {

            $this->createRoutes(function () {

                $this->router->get('users/{id}/{name?}', function (Request $request, $id, $name = 'admin') {

                    return $name.$id;

                });

            });

            $request = $this->webRequest('GET', '/users/1/calvin');
            $this->runAndAssertOutput('calvin1', $request);

            $request = $this->webRequest('GET', 'users/1');
            $this->runAndAssertOutput('admin1', $request);


        }

        /** @test */
        public function multiple_parameters_can_optional_with_a_preceding_capturing_group()
        {

            $this->createRoutes(function () {

                // Preceding Group is capturing
                $this->router->post('/team/{id:\d+}/{name?}/{player?}')
                             ->handle(function (Request $request, $id, $name = 'foo_team', $player = 'foo_player') {

                                 return $name.':'.$id.':'.$player;

                             });

            });

            $response = $this->webRequest('post', '/team/1/dortmund/calvin');
            $this->runAndAssertOutput('dortmund:1:calvin', $response);

            $response = $this->webRequest('post', '/team/1/dortmund');
            $this->runAndAssertOutput('dortmund:1:foo_player', $response);

            $response = $this->webRequest('post', '/team/12');
            $this->runAndAssertOutput('foo_team:12:foo_player', $response);


        }

        /** @test */
        public function multiple_params_can_be_optional_with_preceding_non_capturing_group()
        {

            $this->createRoutes(function () {

                // Preceding group is required but not capturing
                $this->router->post('/users/{name?}/{gender?}/{age?}')
                             ->handle(function (Request $request, $name = 'john', $gender = 'm', $age = '21') {


                                 return $name.':'.$gender.':'.$age;

                             });

            });

            $response = $this->webRequest('post', '/users/calvin/male/23');
            $this->runAndAssertOutput('calvin:male:23', $response);

            $response = $this->webRequest('post', '/users/calvin/male');
            $this->runAndAssertOutput('calvin:male:21', $response);

            $response = $this->webRequest('post', '/users/calvin');
            $this->runAndAssertOutput('calvin:m:21', $response);

            $response = $this->webRequest('post', '/users');
            $this->runAndAssertOutput('john:m:21', $response);

        }

        /** @test */
        public function optional_params_can_match_only_with_trailing_slash_if_desired()
        {

            WP::shouldReceive('usesTrailingSlashes')->andReturnTrue();

            $this->createRoutes(function () {

                // Preceding group is required but not capturing
                $this->router->post('/users/{name?}/{gender?}/{age?}/')
                             ->handle(function (Request $request, $name = 'john', $gender = 'm', $age = '21') {

                                 return $name.':'.$gender.':'.$age;

                             })
                             ->andOnlyTrailing();

            });

            $request = $this->webRequest('post', '/users/');
            $this->runAndAssertOutput('john:m:21', $request);

            $request = $this->webRequest('post', '/users/calvin/');
            $this->runAndAssertOutput('calvin:m:21', $request);

            $request = $this->webRequest('post', '/users/calvin/male/');
            $this->runAndAssertOutput('calvin:male:21', $request);

            $request = $this->webRequest('post', '/users/calvin/male/23/');
            $this->runAndAssertOutput('calvin:male:23', $request);

            $request = $this->webRequest('post', '/users/calvin');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('post', '/users/calvin/male');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('post', '/users/calvin/male/23');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('post', '/users');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function optional_parameters_work_with_our_custom_api()
        {


            $this->createRoutes(function () {

                $this->router->get('users/{id}/{name?}', function (Request $request, $id, $name = 'admin') {

                    return $name.$id;

                })->and('name', '[a-z]+');


            });

            $request = $this->webRequest('GET', '/users/1/calvin');
            $this->runAndAssertOutput('calvin1', $request);

            $request = $this->webRequest('GET', 'users/1');
            $this->runAndAssertOutput('admin1', $request);

            $request = $this->webRequest('GET', 'users/1/12');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function multiple_parameters_can_be_optional_and_have_custom_regex()
        {

            $this->createRoutes(function () {


                // Preceding Group is capturing
                $this->router->post('/team/{id}/{name?}/{age?}')
                             ->and(['name' => '[a-z]+', 'age' => '\d+'])
                             ->handle(function (Request $request, $id, $name = 'foo_team', $age = 21) {

                                 return $name.':'.$id.':'.$age;

                             });

            });

            $response = $this->webRequest('post', '/team/1/dortmund/23');
            $this->runAndAssertOutput('dortmund:1:23', $response);

            $response = $this->webRequest('post', '/team/1/dortmund');
            $this->runAndAssertOutput('dortmund:1:21', $response);

            $response = $this->webRequest('post', '/team/12');
            $this->runAndAssertOutput('foo_team:12:21', $response);

            $response = $this->webRequest('post', '/team/1/dortmund/fail');
            $this->runAndAssertEmptyOutput($response);

            $response = $this->webRequest('post', '/team/1/123/123');
            $this->runAndAssertEmptyOutput($response);


        }

        /** @test */
        public function adding_regex_can_be_done_as_a_fluent_api()
        {


            $this->createRoutes(function () {

                $this->router->get('users/{user_id}/{name}', function () {

                    return 'foo';

                })->and('user_id', '[0-9]+')->and('name', 'calvin');

            });

            $request = $this->webRequest('GET', '/users/1/calvin');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/1/john');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/users/w/calvin');
            $this->runAndAssertEmptyOutput($request);

        }

        /** @test */
        public function only_alpha_can_be_added_to_a_segment_as_a_helper_method()
        {

            $this->createRoutes(function () {

                $this->router->get('users/{name}', function () {

                    return 'foo';

                })->andAlpha('name');

                $this->router->get('teams/{name}/{player}', function () {

                    return 'foo';

                })->andAlpha('name', 'player');

                $this->router->get('countries/{country}/{city}', function () {

                    return 'foo';

                })->andAlpha(['country', 'city']);

            });

            $request = $this->webRequest('GET', '/users/calvin');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/cal1vin');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/teams/dortmund/calvin');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/teams/1/calvin');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/teams/dortmund/1');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/countries/germany/berlin');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/countries/germany/1');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/countries/1/berlin');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function only_alphanumerical_can_be_added_to_a_segment_as_a_helper_method()
        {

            $this->createRoutes(function () {


                $this->router->get('users/{name}', function () {

                    return 'foo';

                })->andAlphaNumerical('name');
            });

            $request = $this->webRequest('GET', '/users/calvin');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/calv1in');
            $this->runAndAssertOutput('foo', $request);


        }

        /** @test */
        public function only_number_can_be_added_to_a_segment_as_a_helper_method()
        {

            $this->createRoutes(function () {

                $this->router->get('users/{name}', function () {

                    return 'foo';

                })->andNumber('name');

            });

            $request = $this->webRequest('GET', '/users/1');
            $this->runAndAssertOutput('foo', $request);

            $request = $this->webRequest('GET', '/users/calvin');
            $this->runAndAssertEmptyOutput($request);

            $request = $this->webRequest('GET', '/users/calv1in');
            $this->runAndAssertEmptyOutput($request);


        }

        /** @test */
        public function only_one_of_can_be_added_to_a_segment_as_a_helper_method()
        {

            $this->createRoutes(function () {

                $this->router->get('home/{locale}', function (Request $request, $locale) {

                    return $locale;

                })->andEither('locale', ['en', 'de']);

            });



            $request = $this->webRequest('GET', '/home/en');
            $this->runAndAssertOutput('en', $request);

            $request = $this->webRequest('GET', '/home/de');
            $this->runAndAssertOutput('de', $request);

            $request = $this->webRequest('GET', '/home/es');
            $this->runAndAssertEmptyOutput($request);


        }


    }