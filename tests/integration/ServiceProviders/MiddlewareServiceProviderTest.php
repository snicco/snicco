<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Middleware\Core\EvaluateResponseMiddleware;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Middleware\MiddlewareStack;
    use WPEmerge\Routing\Pipeline;

    class MiddlewareServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function middleware_aliases_are_bound () {

            $this->newTestApp();

            $aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('auth', $aliases);
            $this->assertArrayHasKey('can', $aliases);
            $this->assertArrayHasKey('guest', $aliases);

        }

        /** @test */
        public function the_middleware_groups_are_correctly () {

            $this->newTestApp();

            $groups = TestApp::config('middleware.groups');

            $this->assertSame([], $groups['web']);
            $this->assertSame([], $groups['ajax']);
            $this->assertSame([], $groups['admin']);
            $this->assertSame([], $groups['global']);


        }

        /** @test */
        public function the_middleware_priority_is_extended () {

            $this->newTestApp();

            $priority = TestApp::config('middleware.priority');

            $this->assertSame([], $priority);


        }

        /** @test */
        public function middleware_is_not_run_without_matching_routes_by_default () {


            $this->newTestApp();

            $setting = TestApp::config('middleware.always_run_global', '');

            $this->assertFalse($setting);


        }

        /** @test */
        public function the_unique_middleware_is_extended () {

            $this->newTestApp();

            $unique = TestApp::config('middleware.unique');

            $this->assertSame([], $unique);


        }

        /** @test */
        public function all_services_are_built_correctly () {

            $this->newTestApp();

            $this->assertInstanceOf(EvaluateResponseMiddleware::class, TestApp::resolve(EvaluateResponseMiddleware::class));
            $this->assertInstanceOf(RouteRunner::class, TestApp::resolve(RouteRunner::class));
            $this->assertInstanceOf(MiddlewareStack::class, TestApp::resolve(MiddlewareStack::class));

        }

        /** @test */
        public function the_middleware_pipeline_is_not_a_singleton () {

            $this->newTestApp();

            $pipeline1 = TestApp::resolve(Pipeline::class);
            $pipeline2 = TestApp::resolve(Pipeline::class);

            $this->assertInstanceOf(Pipeline::class, $pipeline1);
            $this->assertInstanceOf(Pipeline::class, $pipeline2);

            $this->assertNotSame($pipeline1, $pipeline2);

        }

    }
