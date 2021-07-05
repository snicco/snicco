<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateTestSubjects;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestViewFactory;
    use Tests\UnitTest;
    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Contracts\AbstractRedirector;
    use WPMvc\Support\WP;
    use WPMvc\Http\Redirector;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\Routing\Router;
    use WPMvc\Routing\UrlGenerator;
    use WPMvc\View\ViewFactory;

    class RedirectRoutesTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;
        use CreateUrlGenerator;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
            $this->container->instance(ViewFactory::class, new TestViewFactory());
            $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);
            HeaderStack::reset();

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();
            HeaderStack::reset();

        }

        /** @test */
        public function a_redirect_route_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->redirect('/foo', '/bar');

            });
            $this->newRedirector();

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('', $request);

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/bar');

        }

        /** @test */
        public function a_permanent_redirect_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->permanentRedirect('/foo', '/bar');

            });
            $this->newRedirector();

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('', $request);

            HeaderStack::assertHasStatusCode(301);
            HeaderStack::assertContains('Location', '/bar');

        }

        /** @test */
        public function a_temporary_redirect_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->temporaryRedirect('/foo', '/bar');

            });

            $this->newRedirector();

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('', $request);

            HeaderStack::assertHasStatusCode(307);
            HeaderStack::assertContains('Location', '/bar');

        }

        /** @test */
        public function a_redirect_to_an_external_url_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->redirectAway('/foo', 'https://foobar.com/', 303);

            });

            $this->newRedirector();

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('', $request);

            HeaderStack::assertHasStatusCode(303);
            HeaderStack::assertContains('Location', 'https://foobar.com/');

        }

        /** @test */
        public function a_redirect_to_a_route_can_be_created()
        {

            $this->createRoutes(function () {

                $this->router->get('/base/{param}', function () {
                    //
                })->name('base');

                $this->router->redirectToRoute('/foo', 'base', ['param' => 'baz'], 304);


            });

            $this->newRedirector();

            $request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('', $request);

            HeaderStack::assertHasStatusCode(304);
            HeaderStack::assertContains('Location', 'base/baz');

        }

        /** @test */
        public function regex_based_redirects_works()

        {

            $this->createRoutes(function () {

                $this->router->redirect('base/{slug}', 'base/new')
                             ->andEither('slug', ['foo', 'bar']);

                $this->router->get('base/biz', function () {

                    return 'biz';
                });

                $this->router->get('base/{path}', function (string $path) {

                    return $path;
                })->andEither('path', ['bam', 'boom']);

            });

            $this->newRedirector();

            $request = $this->webRequest('GET', 'base/foo');
            $this->runAndAssertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/base/new');
            HeaderStack::reset();

            $request = $this->webRequest('GET', 'base/bar');
            $this->runAndAssertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/base/new');
            HeaderStack::reset();

            $request = $this->webRequest('GET', 'base/baz');
            $this->runAndAssertOutput('', $request);
            HeaderStack::assertHasNone();
            HeaderStack::reset();

            $request = $this->webRequest('GET', 'base/biz');
            $this->runAndAssertOutput('biz', $request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();

            $request = $this->webRequest('GET', 'base/boom');
            $this->runAndAssertOutput('boom', $request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();

            $request = $this->webRequest('GET', 'base/bam');
            $this->runAndAssertOutput('bam', $request);
            HeaderStack::assertHasStatusCode(200);
            HeaderStack::reset();


        }

        private function newRedirector()
        {

            $this->container->instance(
                AbstractRedirector::class,
                new Redirector($this->newUrlGenerator(), $this->psrResponseFactory())
            );
        }

    }