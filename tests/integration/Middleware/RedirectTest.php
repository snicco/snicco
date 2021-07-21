<?php


    namespace Tests\integration\Middleware;

    use Snicco\Middleware\Redirect;
    use Tests\TestCase;

    class RedirectTest extends TestCase
    {

        /** @test */
        public function testRedirectForConfiguredUrls()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
            ]));

            $response = $this->get('/foo');
            $response->assertNotNullResponse();
            $response->assertRedirect('/bar')->assertStatus(301);

        }

        private function getMiddleware(array $redirects = [], string $cache_file = null) : Redirect
        {

            return new Redirect($redirects, $cache_file);
        }

        /** @test */
        public function redirects_dont_have_to_match_exactly_trailing_slashes()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
            ]));

            $response = $this->get('/foo')->assertRedirect('/bar');

        }

        /** @test */
        public function other_requests_are_not_redirected()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
            ]));

            $response = $this->get('/bogus/');
            $response->assertNullResponse();
        }

        /** @test */
        public function test_redirects_can_have_a_custom_status_code()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
                302 => [
                    '/baz' => '/biz',
                    '/a/' => '/b/',
                ],
                307 => [
                    '/boo/' => '/bam/',
                ],
            ]));

            $this->get('/foo')->assertRedirect('/bar')->assertStatus(301);
            $this->get('/baz')->assertRedirect('/biz')->assertStatus(302);
            $this->get('/boo')->assertRedirect('/bam')->assertStatus(307);

        }

        /** @test */
        public function the_redirect_map_can_create_a_cache_file()
        {

            $file = __DIR__.DIRECTORY_SEPARATOR.'redirects.json';
            $this->assertFalse(file_exists($file));

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
                302 => [
                    '/baz' => '/biz',
                    '/a/' => '/b/',
                ],
                307 => [
                    '/boo/' => '/bam/',
                ],
            ], $file));

            $this->get('/foo')->assertRedirect('/bar')->assertStatus(301);

            $this->assertTrue(file_exists($file), 'Redirect map not cached.');

            $this->unlink($file);

        }

        /** @test */
        public function redirects_are_not_loaded_from_the_cache_file_if_the_cache_argument_is_omitted()
        {

            $file = __DIR__.DIRECTORY_SEPARATOR.'redirects.json';

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                ],
                302 => [
                    '/baz' => '/biz',
                    '/a/' => '/b/',
                ],
                307 => [
                    '/boo/' => '/bam/',
                ],
            ], $file));

            $this->get('/foo')->assertRedirect('/bar')->assertStatus(301);

            $this->assertTrue(file_exists($file), 'Redirect map not cached.');

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/other',
                ],
            ]));

            $this->get('/foo')->assertRedirect('/other')->assertStatus(301);

        }

        /** @test */
        public function testRedirectsMatchTheFullPathIncludingQueryParams()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo?page=60' => '/bar',
                ],
            ]));

            $response = $this->get('/foo?page=60');
            $response->assertNotNullResponse();
            $response->assertRedirect('/bar')->assertStatus(301);

        }

        /** @test */
        public function two_urls_can_be_redirected_to_the_same_location()
        {

            $this->withMiddleware($this->getMiddleware([
                301 => [
                    '/foo' => '/bar',
                    '/baz' => '/bar',
                ],
            ]));

            $response = $this->get('/foo');
            $response->assertRedirect('/bar')->assertStatus(301);

            $response = $this->get('/baz');
            $response->assertRedirect('/bar')->assertStatus(301);

        }

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withAddedMiddleware('global', Redirect::class);
                $this->withAddedConfig('middleware.always_run_global', true);

            });
            parent::setUp();

        }

        protected function tearDown() : void
        {

            parent::tearDown();

            if (is_file(__DIR__.DIRECTORY_SEPARATOR.'/redirects.json')) {
                $this->unlink(__DIR__.DIRECTORY_SEPARATOR.'/redirects.json');
            }

        }


    }