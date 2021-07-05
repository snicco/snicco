<?php


    declare(strict_types = 1);


    namespace Tests\unit\Middleware;

    use Carbon\Carbon;
    use Psr\Http\Message\ResponseInterface;
    use Tests\helpers\AssertsResponse;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\stubs\TestRequest;
    use Tests\UnitTest;
    use WPMvc\Controllers\RedirectController;
    use WPMvc\Support\WP;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Response;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\Http\Responses\RedirectResponse;
    use WPMvc\Middleware\Core\OpenRedirectProtection;
    use WPMvc\Routing\Route;

    class OpenRedirectProtectionTest extends UnitTest
    {

        use CreateUrlGenerator;
        use CreateRouteCollection;
        use AssertsResponse;
        use CreateDefaultWpApiMocks;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var Delegate
         */
        private $delegate;

        protected function setUp() : void
        {

            parent::setUp();

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            WP::reset();
            \Mockery::close();
        }

        private function newMiddleware($whitelist = []) : OpenRedirectProtection
        {

            $this->routes = $this->newRouteCollection();

            $route = new Route(['GET'], '/redirect/exit', [RedirectController::class, 'exit']);
            $route->name('redirect.protection');
            $this->routes->add($route);

            $this->response_factory = $this->createResponseFactory();

            $this->delegate = new Delegate(function () {

                return $this->response_factory->make(200);
            });

            $m = new OpenRedirectProtection(SITE_URL, $whitelist);
            $m->setResponseFactory($this->response_factory);
            return $m;

        }

        /** @test */
        public function non_redirect_responses_are_always_allowed()
        {

            $request = TestRequest::from('GET', '/foo');
            $response = $this->newMiddleware()->handle($request, $this->delegate);
            $this->assertStatusCode(200, $response);
            $this->assertInstanceOf(Response::class, $response);

        }

        /** @test */
        public function a_redirect_response_is_allowed_if_its_relative()
        {

            $request = TestRequest::from('GET', '/foo', SITE_URL)->withHeader('referer', SITE_URL);
            $response = $this->newMiddleware()->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->to('foo');

            }));
            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', '/foo', $response);

        }

        /** @test */
        public function a_redirect_response_is_allowed_if_its_absolute_and_to_the_same_host()
        {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', SITE_URL);
            $response = $this->newMiddleware()->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('/bar');

            }));
            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', SITE_URL . '/bar', $response);

        }

         /** @test */
        public function a_redirect_response_is_forbidden_if_its_to_a_non_white_listed_host()
        {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', SITE_URL);
            $response = $this->newMiddleware(['stripe.com'])->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://paypal.com');

            }));

            $this->assertForbiddenRedirect($response, 'https://paypal.com');


        }

        /** @test */
        public function an_url_with_double_leading_slash_is_not_allowed () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo');
            $response = $this->newMiddleware()->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('//bar');

            }));

            $this->assertForbiddenRedirect($response, '//bar');

        }

        /** @test */
        public function absolute_redirects_to_other_hosts_are_not_allowed () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo');
            $response = $this->newMiddleware()->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://foo.com/foo');

            }));

            $this->assertForbiddenRedirect($response, 'https://foo.com/foo' );


        }

        /** @test */
        public function hosts_can_be_whitelisted () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', SITE_URL);
            $response = $this->newMiddleware(['stripe.com'])->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://stripe.com/foo');

            }));

            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', 'https://stripe.com/foo', $response);

        }

        /** @test */
        public function subdomains_can_be_whitelisted_with_regex () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', SITE_URL);

            $response = $this->newMiddleware(['*.stripe.com'])->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://payments.stripe.com/foo');

            }));
            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', 'https://payments.stripe.com/foo', $response);

            $response = $this->newMiddleware(['*.stripe.com'])->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://accounts.stripe.com/foo');

            }));
            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', 'https://accounts.stripe.com/foo', $response);

        }

        /** @test */
        public function redirects_to_external_domains_are_not_allowed_if_not_coming_from_the_same_referer () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', 'https://evil.com');
            $response = $this->newMiddleware(['stripe.com'])->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->absoluteRedirect('https://stripe.com/foo');

            }));

            $this->assertForbiddenRedirect($response, 'https://stripe.com/foo');

        }

        /** @test */
        public function redirects_to_same_domain_paths_are_allowed_from_external_referer () {

            $request = TestRequest::from('GET', '/foo', SITE_URL)->withHeader('referer', 'https://stripe.com');
            $response = $this->newMiddleware()->handle($request, new Delegate(function () {

                return $this->response_factory->redirect()->to('foo');

            }));

            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', '/foo', $response);

        }

        /** @test */
        public function redirects_to_same_site_subdomains_are_allowed () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', SITE_URL);

            $response = $this->newMiddleware()->handle($request, new Delegate(function () {


                $target = 'https://accounts.'.parse_url(SITE_URL, PHP_URL_HOST).'/foo';

                return $this->response_factory->redirect()
                                              ->absoluteRedirect($target);

            }));

            $this->assertStatusCode(302, $response);
            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertHeader('Location', 'https://accounts.'. parse_url(SITE_URL, PHP_URL_HOST) . '/foo', $response);

        }

        /** @test */
        public function redirects_to_same_site_subdomains_are_forbidden_for_different_refs () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo')->withHeader('referer', 'https://evil.com');

            $response = $this->newMiddleware()->handle($request, new Delegate(function () {


                $target = 'https://accounts.'.parse_url(SITE_URL, PHP_URL_HOST).'/foo';

                return $this->response_factory->redirect()
                                              ->absoluteRedirect($target);

            }));

            $this->assertForbiddenRedirect($response,  'https://accounts.'.parse_url(SITE_URL, PHP_URL_HOST).'/foo');

        }

        /** @test */
        public function all_protection_can_be_bypassed_if_using_the_away_method () {

            $request = TestRequest::fromFullUrl('GET', SITE_URL . '/foo');

            $response = $this->newMiddleware()->handle($request, new Delegate(function () {


                $target = 'https://external-site.com';

                return $this->response_factory->redirect()->away($target);

            }));

            $this->assertInstanceOf(RedirectResponse::class, $response);
            $this->assertStatusCode(302, $response);
            $this->assertHeader('Location', 'https://external-site.com',  $response);


        }

        private function assertForbiddenRedirect(ResponseInterface $response, string $intended ) {

            $intended = urlencode($intended);

            $this->assertStringStartsWith('/redirect/exit', $response->getHeaderLine('Location'));
            $this->assertStringContainsString('&intended_redirect='.$intended, $response->getHeaderLine('Location'));
            $this->assertStringContainsString('?expires='.Carbon::now()->addSeconds(10)->getTimestamp(), $response->getHeaderLine('Location'));

        }
    }

