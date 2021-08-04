<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use WP;

    use function apply_filters;

    class FilterWpQueryTest extends TestCase
    {

        protected $defer_boot = true;

        /** @test */
        public function WP_QUERY_vars_can_be_filtered_by_a_route()
        {

            $request = TestRequest::from('GET', '/wpquery/foo');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;

            $wp->parse_request();

            $this->assertSame(['foo' => 'baz'], $wp->query_vars);

            apply_filters('template_include', 'wordpress.php');

            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');


        }

        /** @test */
        public function the_query_can_ONLY_get_filtered_for_read_verbs()
        {

            // The route responds to post but the event won't get dispatched.
            $request = TestRequest::from('POST', '/wpquery/post');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;

            $wp->parse_request();

            $this->assertSame([], $wp->query_vars);

            apply_filters('template_include', 'wordpress.php');
            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');

        }

        /** @test */
        public function captured_route_params_get_passed_to_the_query_filter()
        {

            $request = TestRequest::from('GET', 'wpquery/teams/germany/dortmund');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;
            $wp->parse_request();

            $this->assertSame(['germany' => 'dortmund'], $wp->query_vars);

        }

        /** @test */
        public function the_route_handler_does_not_get_run_when_filtering_WP_QUERY()
        {

            $request = TestRequest::from('GET', '/wpquery/assert-no-handler-run');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;
            $wp->parse_request();

            $this->assertSame(['foo' => 'baz'], $wp->query_vars);

            $this->assertNoResponse();


        }

        /** @test */
        public function its_possible_to_create_routes_that_ONLY_CHANGE_WP_QUERY_but_dont_have_a_route_action()
        {

            $request = TestRequest::from('GET', '/wpquery/do-nothing');
            $this->withRequest($request);
            $this->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;
            $wp->parse_request();

            $this->assertSame(['foo' => 'baz'], $wp->query_vars);

            apply_filters('template_include', 'wp.php');

            $this->assertNoResponse();

        }

        /** @test */
        public function the_WP_QUERY_parsing_flow_remains_the_same_if_no_custom_route_matched()
        {

            $request = TestRequest::from('GET', '/wpquery/bogus');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $request_parsed = false;
            add_action('request', function () use (&$request_parsed) {

                $request_parsed = true;
            });

            /** @var WP $wp */
            global $wp;
            $wp->parse_request();

            $this->assertTrue($request_parsed);

        }

        /** @test */
        public function the_WP_QUERY_flow_is_short_circuited_if_a_custom_route_matched()
        {

            $request = TestRequest::from('GET', '/wpquery/foo');
            $this->withRequest($request)->boot();
            $this->loadRoutes();

            $request_parsed = false;
            add_action('request', function () use (&$request_parsed) {

                $request_parsed = true;
            });

            /** @var WP $wp */
            global $wp;
            $wp->parse_request();

            $this->assertFalse($request_parsed);

        }

    }