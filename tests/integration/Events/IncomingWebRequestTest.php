<?php


    declare(strict_types = 1);


    namespace Tests\integration\Events;

    use Tests\IntegrationTest;

    class IncomingWebRequestTest extends IntegrationTest
    {

        /** @test */
        public function the_wp_query_is_never_set_to_404_during_the_main_wp_function () {

            $this->newTestApp();

            global $wp, $wp_query;

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
            $wp_query->is_paged = true;

            $this->assertFalse($wp_query->is_404());

            $tpl = apply_filters('template_include', 'wp.php');

            $this->assertTrue($wp_query->is_404());

        }

    }