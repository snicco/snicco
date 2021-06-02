<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\SessionServiceProvider;

    class MagicLinkLoginControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;

        private function createSigneddUrl(int $user_id, $intended = '')
        {

            /** @var UrlGenerator $url */
            $url = TestApp::resolve(UrlGenerator::class);

            return $url->signedRoute('auth.confirm.magic-login',
                [
                    'user_id' => $user_id,
                    'query' => [
                        'intended' => $intended,
                    ],
                ]);

        }

        private function newApp()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);
        }

        /** @test */
        public function the_route_cant_be_accessed_without_valid_signature()
        {

            $this->newApp();

            $url = TestApp::routeUrl('auth.confirm.magic-login', ['user_id' => 1]);

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::loginUrl());
            HeaderStack::assertHasStatusCode(403);

        }

        /** @test */
        public function a_redirect_response_is_created_for_user_ids_that_dont_exist()
        {


            $this->newApp();

            $url = $this->createSigneddUrl(999);

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::loginUrl());
            HeaderStack::assertHasStatusCode(404);

        }

        /** @test */
        public function users_get_redirected_to_the_intended_url_from_the_query_string () {


            $calvin = $this->newAdmin([
                'user_id' => 999
            ]);
            $this->login($calvin);
            $this->newApp();

            $url = $this->createSigneddUrl(999, 'https://foobar.com?bar=baz');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', 'https://foobar.com?bar=baz');
            HeaderStack::assertHasStatusCode(200);

        }

    }
