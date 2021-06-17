<?php


    declare(strict_types = 1);


    namespace Tests\integration\Events;

    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;

    class IncomingWebRequest404CompatTest extends IntegrationTest
    {

        /** @test */
        public function the_wp_query_is_never_set_to_404_during_the_main_wp_function () {

            $this->newTestApp();

            global $wp, $wp_query;

            // simulate a 404.
            $wp_query->is_paged = true;

            $this->assertFalse($wp_query->is_404());

            $wp->main();

            $this->assertFalse($wp_query->is_404());


        }

        /** @test */
        public function after_the_template_include_hook_fired_the_wp_query_is_evaluated_for_a_possible_404 () {

            $this->newTestApp();

            global $wp, $wp_query;


            $this->assertFalse($wp_query->is_404());

            $wp->main();

            // simulate a 404.
            $wp_query->is_paged = true;

            $this->assertFalse($wp_query->is_404());

            $tpl = apply_filters('template_include', 'index.php');

            $this->assertTrue($wp_query->is_404());

        }

        /** @test */
        public function the_correct_default_404_template_is_loaded_if_no_route_matched () {

            $this->newTestApp();
            global $wp, $wp_query;


            $this->assertFalse($wp_query->is_404());

            $wp->main();

            // simulate a 404.
            $wp_query->is_paged = true;

            $this->assertFalse($wp_query->is_404());

            $tpl = apply_filters('template_include', 'index.php');

            $this->assertTrue($wp_query->is_404());

            $this->assertNotSame('index.php', $tpl);
            $this->assertStringContainsString('themes/twentytwentyone/404.php', $tpl);

        }

        /** @test */
        public function null_is_returned_if_a_route_matches () {

            $this->newTestApp(TEST_CONFIG);
            $this->rebindRequest(TestRequest::from('GET', 'foo'));

            $this->registerAndRunApiRoutes();


            global $wp, $wp_query;
            $this->assertFalse($wp_query->is_404());

            ob_start();
            $tpl = apply_filters('template_include', 'index.php');
            $this->assertSame('foo', ob_get_clean());

            $this->assertFalse($wp_query->is_404());
            $this->assertNull($tpl);

        }

        /** @test */
        public function the_template_WP_tried_to_load_is_returned_when_no_route_was_found()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/bogus'));

            global $wp_query;

            // Simulate that its not a 404 page.
            $wp_query->is_robots = true;

            ob_start();

            do_action('init');
            $tpl = apply_filters('template_include', 'wp-template.php');

            $this->assertSame('wp-template.php', $tpl);
            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();


        }


    }