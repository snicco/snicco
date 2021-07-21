<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Middleware\NoRobots;

    class NoRobotsTest extends UnitTest
    {

        use CreatePsr17Factories;
        use CreateUrlGenerator;
        use CreateRouteCollection;
        use AssertsResponse;

        /**
         * @var Request
         */
        private $request;

        /**
         * @var Delegate
         */
        private $route_action;

        protected function setUp() : void
        {

            parent::setUp();
            $this->request = TestRequest::from('GET', 'foo');
            $f = $this->createResponseFactory();
            $this->route_action = new Delegate(function () use ($f) {

                return $f->make();


            });


        }

        /** @test */
        public function everything_is_disabled_by_default()
        {

            $middleware = new NoRobots();

            $response = $middleware->handle($this->request, $this->route_action);

            $header = $response->getHeader('X-Robots-Tag');

            $this->assertContains('noindex', $header);
            $this->assertContains('nofollow', $header);
            $this->assertContains('noarchive', $header);


        }

        /** @test */
        public function no_index_can_be_configured_separately () {

            $middleware = new NoRobots('false');

            $response = $middleware->handle($this->request, $this->route_action);

            $header = $response->getHeader('X-Robots-Tag');

            $this->assertNotContains('noindex', $header);
            $this->assertContains('nofollow', $header);
            $this->assertContains('noarchive', $header);

        }

        /** @test */
        public function no_follow_can_be_configured_separately () {

            $middleware = new NoRobots('noindex', 'false');

            $response = $middleware->handle($this->request, $this->route_action);

            $header = $response->getHeader('X-Robots-Tag');

            $this->assertContains('noindex', $header);
            $this->assertNotContains('nofollow', $header);
            $this->assertContains('noarchive', $header);

        }

        /** @test */
        public function no_archive_can_be_configured_separately () {

            $middleware = new NoRobots('noindex', 'nofollow', 'false');

            $response = $middleware->handle($this->request, $this->route_action);

            $header = $response->getHeader('X-Robots-Tag');

            $this->assertContains('noindex', $header);
            $this->assertContains('nofollow', $header);
            $this->assertNotContains('noarchive', $header);

        }

    }