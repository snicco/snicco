<?php


    declare(strict_types = 1);


    namespace Tests\integration\Events;

    use Tests\IntegrationTest;
    use Tests\stubs\TestRequest;

    class IncomingWebRequestTest extends IntegrationTest
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

            $this->registerRoutes();


            global $wp, $wp_query;
            $this->assertFalse($wp_query->is_404());

            ob_start();
            $tpl = apply_filters('template_include', 'index.php');
            $this->assertSame('foo', ob_get_clean());

            $this->assertFalse($wp_query->is_404());
            $this->assertNull($tpl);

        }

    }