<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Nyholm\Psr7\Uri;
    use Tests\helpers\CreatePsr17Factories;
    use Tests\helpers\CreateRouteCollection;
    use Tests\helpers\CreateRouteMatcher;
    use Tests\helpers\CreateUrlGenerator;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\TestMagicLink;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\Authenticators\MagicLinkAuthenticator;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Http\Delegate;

    class MagicLinkAuthenticatorTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use CreatePsr17Factories;
        use CreateUrlGenerator;
        use CreateRouteCollection;
        use CreateRouteMatcher;

        /**
         * @var PasswordAuthenticator
         */
        private $authenticator;

        /**
         * @var Delegate
         */
        private $next;

        /**
         * @var TestRequest
         */
        private $request;

        protected function afterSetup()
        {

            $r = $this->createResponseFactory();
            $this->authenticator = new MagicLinkAuthenticator($this->magic_link);
            $this->authenticator->setResponseFactory($r);
            $this->request = TestRequest::from('GET', '/auth/login');
            $this->next = new Delegate(function () {
            });

        }

        /** @test */
        public function an_invalid_magic_link_will_fail()
        {

            $calvin = $this->newAdmin();

            $url = $this->generator->signed('/auth/login', 300, true, ['user_id' => $calvin->ID]);

            $request = $this->request->withUri(new Uri($url.'a'));

            $this->expectException(FailedAuthenticationException::class);

            $this->authenticator->attempt($request, $this->next);

        }

        /** @test */
        public function a_magic_link_for_a_non_resolvable_user_will_fail()
        {

            $calvin = $this->newAdmin();

            $url = $this->generator->signed('/auth/login', 300, true, ['user_id' => $calvin->ID + 1]);

            $request = $this->request->withUri(new Uri($url));

            $this->expectException(FailedAuthenticationException::class);

            $this->authenticator->attempt($request, $this->next);


        }

        /** @test */
        public function a_valid_link_will_log_the_user_in()
        {

            $calvin = $this->newAdmin();

            $url = $this->generator->signed('/auth/login', 300, true, ['user_id' => $calvin->ID]);

            $request = $this->request->withUri(new Uri($url));

            /** @var SuccessfulLoginResponse $response */
            $response = $this->authenticator->attempt($request, $this->next);

            $this->assertInstanceOf(SuccessfulLoginResponse::class, $response);
            $this->assertSame($calvin->ID, $response->authenticatedUser()->ID);
            $this->assertTrue($response->rememberUser());

            $this->assertSame([], $this->magic_link->getStored(), 'Auth magic link not deleted');

        }


    }