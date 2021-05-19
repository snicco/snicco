<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\UnitTest;
    use Tests\traits\SetUpRouter;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request as Request;

    class RouteSegmentsTest extends UnitTest
    {

        use SetUpRouter;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);
        }

        protected function beforeTearDown()
        {

            Mockery::close();
            WP::clearResolvedInstances();
            WP::setFacadeContainer(null);

        }


        /**
         *
         *
         *
         *
         *
         * ROUTE PARAMETERS, NATIVE FAST ROUTE SYNTAX
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function route_parameters_are_captured()
        {

            $this->router->post('/user/{id}/{name}')
                         ->handle(function (Request $request, $id, $name = 'admin') {

                             return $name.$id;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/user/12/calvin'));
            $this->assertOutput('calvin12', $response);


        }

        /** @test */
        public function custom_regex_can_be_defined_for_route_parameters()
        {


            $this->router->post('/user/{id:\d+}/{name:calvin|john}')
                         ->handle(function (Request $request, $id, $name = 'admin') {

                             return $name.$id;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/user/12/calvin'));
            $this->assertOutput('calvin12', $response);

            $response = $this->router->runRoute($this->request('post', '/user/12/john'));
            $this->assertOutput('john12', $response);

            $response = $this->router->runRoute($this->request('post', '/user/a/calvin'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/user/12/jane'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/user/12'));
            $this->assertNullResponse($response);

        }

        /** @test */
        public function optional_parameters_work_at_the_end_of_a_route()
        {


            $this->router->post('/user/{id:\d+}[/{name}]')
                         ->handle(function (Request $request, $id, $name = 'admin') {

                             return $name.$id;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/user/12/calvin'));
            $this->assertOutput('calvin12', $response);

            $response = $this->router->runRoute($this->request('post', '/user/12'));
            $this->assertOutput('admin12', $response);

            $response = $this->router->runRoute($this->request('post', '/user/ab'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/user/ab/calvin'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/user/calvin/12'));
            $this->assertNullResponse($response);


        }

        /** @test */
        public function every_segment_after_an_optional_part_will_be_its_own_capture_group_but_not_required()
        {


            $this->router->post('/team/{id:\d+}[/{name}[/{player}]]')
                         ->handle(function (Request $request, $id, $name = 'foo_team', $player = 'foo_player') {

                             return $name.':'.$id.':'.$player;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund/calvin'));
            $this->assertOutput('dortmund:1:calvin', $response);

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund'));
            $this->assertOutput('dortmund:1:foo_player', $response);

            $response = $this->router->runRoute($this->request('post', '/team/12'));
            $this->assertOutput('foo_team:12:foo_player', $response);

        }

        /** @test */
        public function optional_parameters_work_with_custom_regex()
        {


            $this->router->get('users/{id}[/{name:[a-z]+}]', function (Request $request, $id, $name = 'admin') {

                return $name.$id;

            });

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1/calvin');
            $this->assertOutput('calvin1', $this->router->runRoute($request));

            $request = $this->request('GET', 'users/1');
            $this->assertOutput('admin1', $this->router->runRoute($request));

            $request = $this->request('GET', 'users/1/12');
            $this->assertNullResponse($this->router->runRoute($request));


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


            $this->router->get('users/{user}', function () {

                return 'foo';

            })->and('user', '[0-9]+');

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/calvin');
            $this->assertNullResponse($this->router->runRoute($request));


        }

        /** @test */
        public function regex_can_be_added_as_a_condition_as_array_syntax()
        {


            $this->router->get('users/{user}', function () {

                return 'foo';

            })->and(['user', '[0-9]+']);
            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/calvin');
            $this->assertNullResponse($this->router->runRoute($request));


        }

        /** @test */
        public function multiple_regex_conditions_can_be_added_to_an_url_condition()
        {


            $this->router->get('/user/{id}/{name}', function (Request $request, $id, $name) {

                return $name.$id;

            })->and(['id' => '[0-9]+', 'name' => '[a-z]+']);
            $this->router->loadRoutes();

            $request = $this->request('GET', '/user/1/calvin');
            $this->assertOutput('calvin1', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/1/1');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/users/calvin/calvin');
            $this->assertNullResponse($this->router->runRoute($request));

        }

        /** @test */
        public function optional_parameters_work_at_the_end_of_the_url()
        {


            $this->router->get('users/{id}/{name?}', function (Request $request, $id, $name = 'admin') {

                return $name.$id;

            });
            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1/calvin');
            $this->assertOutput('calvin1', $this->router->runRoute($request));

            $request = $this->request('GET', 'users/1');
            $this->assertOutput('admin1', $this->router->runRoute($request));


        }

        /** @test */
        public function multiple_parameters_can_optional_with_a_preceding_capturing_group()
        {

            // Preceding Group is capturing
            $this->router->post('/team/{id:\d+}/{name?}/{player?}')
                         ->handle(function (Request $request, $id, $name = 'foo_team', $player = 'foo_player') {

                             return $name.':'.$id.':'.$player;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund/calvin'));
            $this->assertOutput('dortmund:1:calvin', $response);

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund'));
            $this->assertOutput('dortmund:1:foo_player', $response);

            $response = $this->router->runRoute($this->request('post', '/team/12'));
            $this->assertOutput('foo_team:12:foo_player', $response);


        }

        /** @test */
        public function multiple_params_can_be_optional_with_preceding_non_capturing_group()
        {

            // Preceding group is required but not capturing
            $this->router->post('/users/{name?}/{gender?}/{age?}')
                         ->handle(function (Request $request, $name = 'john', $gender = 'm', $age = '21') {


                             return $name.':'.$gender.':'.$age;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male/23'));
            $this->assertOutput('calvin:male:23', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male'));
            $this->assertOutput('calvin:male:21', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin'));
            $this->assertOutput('calvin:m:21', $response);

            $response = $this->router->runRoute($this->request('post', '/users'));
            $this->assertOutput('john:m:21', $response);

        }


        /** @test */
        public function optional_params_can_match_only_with_trailing_slash_if_desired()
        {

            // Preceding group is required but not capturing
            $this->router->post('/users/{name?}/{gender?}/{age?}')
                         ->handle(function (Request $request, $name = 'john', $gender = 'm', $age = '21') {

                             return $name.':'.$gender.':'.$age;

                         })
                         ->andOnlyTrailing();

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/users/'));
            $this->assertOutput('john:m:21', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/'));
            $this->assertOutput('calvin:m:21', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male/'));
            $this->assertOutput('calvin:male:21', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male/23/'));
            $this->assertOutput('calvin:male:23', $response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/users/calvin/male/23'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/users'));
            $this->assertNullResponse($response);


        }


        /** @test */
        public function optional_parameters_work_with_our_custom_api()
        {


            $this->router->get('users/{id}/{name?}', function (Request $request, $id, $name = 'admin') {

                return $name.$id;

            })->and('name', '[a-z]+');

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1/calvin');
            $this->assertOutput('calvin1', $this->router->runRoute($request));

            $request = $this->request('GET', 'users/1');
            $this->assertOutput('admin1', $this->router->runRoute($request));

            $request = $this->request('GET', 'users/1/12');
            $this->assertNullResponse($this->router->runRoute($request));


        }

        /** @test */
        public function multiple_parameters_can_be_optional_and_have_custom_regex()
        {

            // Preceding Group is capturing
            $this->router->post('/team/{id}/{name?}/{age?}')
                         ->and(['name' => '[a-z]+', 'age' => '\d+'])
                         ->handle(function (Request $request, $id, $name = 'foo_team', $age = 21) {

                             return $name.':'.$id.':'.$age;

                         });

            $this->router->loadRoutes();

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund/23'));
            $this->assertOutput('dortmund:1:23', $response);

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund'));
            $this->assertOutput('dortmund:1:21', $response);

            $response = $this->router->runRoute($this->request('post', '/team/12'));
            $this->assertOutput('foo_team:12:21', $response);

            $response = $this->router->runRoute($this->request('post', '/team/1/dortmund/fail'));
            $this->assertNullResponse($response);

            $response = $this->router->runRoute($this->request('post', '/team/1/123/123'));
            $this->assertNullResponse($response);


        }

        /** @test */
        public function adding_regex_can_be_done_as_a_fluent_api()
        {


            $this->router->get('users/{user_id}/{name}', function () {

                return 'foo';

            })->and('user_id', '[0-9]+')->and('name', 'calvin');

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1/calvin');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/1/john');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/users/w/calvin');
            $this->assertNullResponse($this->router->runRoute($request));

        }

        /** @test */
        public function only_alpha_can_be_added_to_a_segment_as_a_helper_method()
        {


            $this->router->get('users/{name}', function () {

                return 'foo';

            })->andAlpha('name');

            $this->router->get('teams/{name}/{player}', function () {

                return 'foo';

            })->andAlpha('name', 'player');

            $this->router->get('countries/{country}/{city}', function () {

                return 'foo';

            })->andAlpha(['country', 'city']);

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/calvin');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/cal1vin');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/teams/dortmund/calvin');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/teams/1/calvin');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/teams/dortmund/1');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/countries/germany/berlin');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/countries/germany/1');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/countries/1/berlin');
            $this->assertNullResponse($this->router->runRoute($request));


        }

        /** @test */
        public function only_alphanumerical_can_be_added_to_a_segment_as_a_helper_method()
        {


            $this->router->get('users/{name}', function () {

                return 'foo';

            })->andAlphaNumerical('name');

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/calvin');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/calv1in');
            $this->assertOutput('foo', $this->router->runRoute($request));


        }

        /** @test */
        public function only_number_can_be_added_to_a_segment_as_a_helper_method()
        {


            $this->router->get('users/{name}', function () {

                return 'foo';

            })->andNumber('name');

            $this->router->loadRoutes();

            $request = $this->request('GET', '/users/1');
            $this->assertOutput('foo', $this->router->runRoute($request));

            $request = $this->request('GET', '/users/calvin');
            $this->assertNullResponse($this->router->runRoute($request));

            $request = $this->request('GET', '/users/calv1in');
            $this->assertNullResponse($this->router->runRoute($request));


        }

        /** @test */
        public function only_one_of_can_be_added_to_a_segment_as_a_helper_method()
        {


            $this->router->get('home/{locale}', function (Request $request, $locale) {

                return $locale;

            })->andEither('locale', ['en', 'de']);

            $this->router->loadRoutes();

            $request = $this->request('GET', '/home/en');
            $this->assertOutput('en', $this->router->runRoute($request));

            $request = $this->request('GET', '/home/de');
            $this->assertOutput('de', $this->router->runRoute($request));

            $request = $this->request('GET', '/home/es');
            $this->assertNullResponse($this->router->runRoute($request));


        }


    }