<?php


    declare(strict_types = 1);


    namespace Tests\integration\Http;

    use Tests\stubs\TestRequest;
    use Tests\TestCase;

    class FilterWpQueryTest extends TestCase
    {

        protected $defer_boot = true;

        /** @test */
        public function WP_QUERY_vars_can_be_filtered_by_a_route()
        {

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('GET', '/wpquery/foo');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $after = apply_filters('request', $query_vars);
            $this->assertSame(['foo' => 'baz'], $after);

            apply_filters('template_include', 'wordpress.php');

            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');


        }

        /** @test */
        public function the_query_can_ONLY_get_filtered_for_read_verbs()
        {

            $query_vars = ['foo' => 'bar'];

            // The route responds to post but the event wont get dispatched.
            $request = TestRequest::from('POST', '/wpquery/post');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $after = apply_filters('request', $query_vars);
            $this->assertSame(['foo' => 'bar'], $after);

            apply_filters('template_include', 'wordpress.php');
            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');

        }

        /** @test */
        public function WP_QUERY_remains_unchanged_when_no_route_matches()
        {

            $query_vars = ['foo' => 'bar'];

            $request = TestRequest::from('GET', '/wpquery/bogus');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $after = apply_filters('request', $query_vars);

            $this->assertSame(['foo' => 'bar'], $after);

        }

        /** @test */
        public function captured_route_params_get_passed_to_the_query_filter()
        {

            $query_vars = ['spain' => 'barcelona'];
            $request = TestRequest::from('GET', 'wpquery/teams/germany/dortmund');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $after = apply_filters('request', $query_vars);

            $this->assertSame(['spain' => 'barcelona', 'germany' => 'dortmund'], $after);

        }

        /** @test */
        public function the_route_handler_does_not_get_run_when_filtering_WP_QUERY()
        {

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('GET', '/wpquery/assert-no-handler-run');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $after = apply_filters('request', $query_vars);

            $this->assertSame(['foo' => 'baz'], $after);

            $this->assertNoResponse();

        }

        /** @test */
        public function its_possible_to_create_routes_that_ONLY_CHANGE_WP_QUERY_but_dont_have_a_route_action()
        {

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('GET', '/wpquery/do-nothing');
            $this->withRequest($request);
            $this->boot();
            $this->loadRoutes();

            $filter_WP_QUERY = apply_filters('request', $query_vars);
            $this->assertSame(['foo' => 'baz'], $filter_WP_QUERY);

            apply_filters('template_include', 'wp.php');

            $this->assertNoResponse();

        }


    }