<?php

    declare(strict_types = 1);


    namespace Tests\integration\ExceptionHandling;

    use Tests\helpers\CreatesWpUrls;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Events\IncomingAdminRequest;

    class ProductionErrorHandlingTest extends IntegrationTest
    {

        use CreatesWpUrls;

        protected function setUp() : void
        {

            parent::setUp();

        }

        private function config () : array
        {
            return array_merge(TEST_CONFIG, [
                'exception_handling' =>[
                    'enabled' => true
                ]
            ]);

        }

        /** @test */
        public function a_http_exception_rendered_into_a_generic_error_page () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest(TestRequest::from('GET', 'error/500'));
            $this->registerRoutes();

            ob_start();

            $tpl = apply_filters('template_include', 'wp.php');

            $output = ob_get_clean();

            $this->assertSame('VIEW:error.php,STATUS:500,MESSAGE:Something went wrong here.', $output);
            HeaderStack::assertHasStatusCode(500);
            HeaderStack::assertHas('Content-Type', 'text/html');

        }

        /** @test */
        public function http_exceptions_transformed_into_specific_status_code_views_if_they_exists () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest(TestRequest::from('GET', 'error/419'));
            $this->registerRoutes();

            ob_start();

            $tpl = apply_filters('template_include', 'wp.php');

            $output = ob_get_clean();

            $this->assertSame('VIEW:419.php,STATUS:419,MESSAGE:The Link you followed expired.', $output);
            HeaderStack::assertHasStatusCode(419);
            HeaderStack::assertHas('Content-Type', 'text/html');

        }

        /** @test */
        public function a_http_exception_which_cant_be_mapped_with_a_status_code_gets_rendered_with_the_default_error_view () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest(TestRequest::from('GET', 'error/400'));
            $this->registerRoutes();

            ob_start();

            $tpl = apply_filters('template_include', 'wp.php');

            $output = ob_get_clean();

            $this->assertSame('VIEW:error.php,STATUS:400,MESSAGE:Bad Request.', $output);
            HeaderStack::assertHasStatusCode(400);
            HeaderStack::assertHas('Content-Type', 'text/html');

        }

        /** @test */
        public function an_admin_request_can_have_specific_admin_error_views_that_have_priority_over_non_admin_views () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest($request = $this->adminRequestTo('error'));
            $this->registerRoutes();

            ob_start();

            IncomingAdminRequest::dispatch([$request]);

            $output = ob_get_clean();

            $this->assertSame('VIEW:419-admin.php,STATUS:419,MESSAGE:The Link you followed expired.', $output);
            HeaderStack::assertHasStatusCode(419);
            HeaderStack::assertHas('Content-Type', 'text/html');

        }

        /** @test */
        public function request_that_expect_json_are_rendered_as_json () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest(TestRequest::from('GET', 'error/500')->withAddedHeader('Accept', 'application/json'));
            $this->registerRoutes();

            ob_start();

            $tpl = apply_filters('template_include', 'wp.php');

            $output = ob_get_clean();

            $this->assertSame(json_encode('Something went wrong here.'), $output);
            HeaderStack::assertHasStatusCode(500);
            HeaderStack::assertHas('Content-Type', 'application/json');

        }

        /** @test */
        public function errors_that_can_not_be_converted_to_any_at_all_are_rendered_with_the_default_error_view () {

            $this->newTestApp($this->config(), true );

            $this->rebindRequest(TestRequest::from('GET', 'error/fatal'));
            $this->registerRoutes();

            ob_start();

            $tpl = apply_filters('template_include', 'wp.php');

            $output = ob_get_clean();

            $this->assertSame('VIEW:error.php,STATUS:500,MESSAGE:Internal Server Error', $output);
            HeaderStack::assertHasStatusCode(500);
            HeaderStack::assertHas('Content-Type', 'text/html');

        }

    }