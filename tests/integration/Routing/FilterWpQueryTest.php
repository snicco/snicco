<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\TestCase;
    use WP;

    class FilterWpQueryTest extends TestCase
    {

        protected bool $defer_boot = true;

        /** @test */
        public function WP_QUERY_vars_can_be_filtered_by_a_route()
        {

            $this->withRequest($this->frontendRequest('GET', '/wpquery/foo'))->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;

            $wp->main();

            $this->assertSame(['foo' => 'baz'], $wp->query_vars);

            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');


        }

        /** @test */
        public function the_query_can_ONLY_get_filtered_for_read_verbs()
        {

            // The route responds to post but the event won't get dispatched.
            $this->withRequest($this->frontendRequest('POST', '/wpquery/post'))->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;

            $wp->main();

            $this->assertSame([], $wp->query_vars);

            $this->sentResponse()->assertOk()->assertSee('FOO_QUERY');

        }

        /** @test */
        public function captured_route_params_get_passed_to_the_query_filter()
        {

            $this->withRequest($this->frontendRequest('GET', '/wpquery/teams/germany/dortmund'))
                 ->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;
            $wp->main();

            $this->assertSame(['germany' => 'dortmund'], $wp->query_vars);

        }

        /** @test */
        public function the_route_handler_does_not_get_run_when_filtering_WP_QUERY()
        {

            $this->withRequest($this->frontendRequest('GET', '/wpquery/assert-no-driver-run'))
                 ->boot();
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

            $this->withRequest($this->frontendRequest('GET', '/wpquery/do-nothing'))->boot();
            $this->loadRoutes();

            /** @var WP $wp */
            global $wp;
            $wp->main();

            $this->assertSame(['foo' => 'baz'], $wp->query_vars);

            $this->assertNoResponse();

        }

        /** @test */
        public function the_WP_QUERY_parsing_flow_remains_the_same_if_no_custom_route_matched()
        {

            $this->withRequest($this->frontendRequest('GET', '/wpquery/bogus'))->boot();
            $this->loadRoutes();

            $request_parsed = false;
            add_action('request', function ($query_vars) use (&$request_parsed) {

                $request_parsed = true;

                return $query_vars;
            });

            /** @var WP $wp */
            global $wp;
            $wp->main();

            $this->assertTrue($request_parsed);

        }

        /** @test */
        public function the_WP_QUERY_flow_is_short_circuited_if_a_custom_route_matched()
        {

            $this->withRequest($this->frontendRequest('GET', '/wpquery/foo'))->boot();
            $this->loadRoutes();

            $request_parsed = false;
            add_action('request', function () use (&$request_parsed) {

                $request_parsed = true;
            });

            /** @var WP $wp */
            global $wp;
            $wp->main();

            $this->assertFalse($request_parsed);

        }

    }