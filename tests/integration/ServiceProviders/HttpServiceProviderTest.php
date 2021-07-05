<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\helpers\CreatesWpUrls;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\Http\Cookies;
    use BetterWP\Http\HttpKernel;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Redirector;
    use BetterWP\Http\ResponseFactory;

    class HttpServiceProviderTest extends TestCase
    {

        use CreatesWpUrls;

        protected $defer_boot = true;

        /** @test */
        public function the_kernel_can_be_resolved_correctly()
        {

            $this->boot();
            $this->assertInstanceOf(HttpKernel::class, TestApp::resolve(HttpKernel::class));


        }

        /** @test */
        public function the_response_factory_can_be_resolved()
        {

            $this->boot();

            $this->assertInstanceOf(ResponseFactory::class, TestApp::resolve(ResponseFactory::class));

        }

        /** @test */
        public function the_cookie_instance_can_be_resolved()
        {

            $this->boot();

            $this->assertInstanceOf(Cookies::class, TestApp::resolve(Cookies::class));

        }

        /** @test */
        public function the_redirector_can_be_resolved()
        {

            $this->boot();

            $this->assertInstanceOf(Redirector::class, TestApp::resolve(AbstractRedirector::class));

        }

        /** @test */
        public function the_ajax_request_endpoint_is_detected_correctly()
        {

            $this->withRequest($this->ajaxRequest('foo'));
            $this->boot();

            $this->assertSame('wp_ajax', TestApp::config('_request_endpoint'));

        }

        /** @test */
        public function the_admin_request_endpoint_is_detected_correctly()
        {

            $this->withRequest($this->adminRequestTo('foo'));
            $this->boot();

            $this->assertSame('wp_admin', TestApp::config('_request_endpoint'));

        }

        /** @test */
        public function an_api_request_endpoint_is_detected_correctly()
        {

            $this->withRequest(TestRequest::from('GET', '/api-prefix/base/foo'));
            $this->boot();

            $this->assertSame('api', TestApp::config('_request_endpoint'));

        }

        /** @test */
        public function the_default_request_type_is_frontend () {

            $this->withRequest(TestRequest::from('GET', 'foo'));
            $this->boot();

            $this->assertSame('frontend', TestApp::config('_request_endpoint'));

        }

        /** @test */
        public function the_api_endpoints_are_shared_with_the_request () {

            $this->boot();

            /** @var Request $request */
            $request = $this->app->resolve(Request::class);

            $this->assertNotEmpty($request->getAttribute('_api.endpoints'));

        }


    }

