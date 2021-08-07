<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use Snicco\Contracts\EncryptorInterface;

    use function update_user_meta;

    class TwoFactorAuthSessionControllerTest extends AuthTestCase
    {

        protected function setUp() : void
        {
	
	        $this->afterLoadingConfig(function() {
		
		        $this->with2Fa();
	        });
	        $this->afterApplicationCreated(function() {
		        $this->encryptor = $this->app->resolve(EncryptorInterface::class);
	        });
	
	        parent::setUp();
	
        }

        /** @test */
        public function the_controller_cant_be_accessed_if_2fa_is_disabled()
        {

            $this->without2Fa();

            $response = $this->get('/auth/two-factor/challenge');

            $response->assertNullResponse();

        }

        /** @test */
        public function the_auth_challenge_is_not_rendered_if_a_user_is_not_challenged()
        {

            $calvin = $this->createAdmin();
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $response = $this->get('/auth/two-factor/challenge');

            $response->assertRedirectToRoute('auth.login');


        }

        /** @test */
        public function the_auth_challenge_is_not_rendered_if_a_user_is_not_using_2fa()
        {

            $calvin = $this->createAdmin();
            $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);

            $response = $this->get('/auth/two-factor/challenge');

            $response->assertRedirectToRoute('auth.login');


        }

        /** @test */
        public function the_2fa_challenge_screen_can_be_rendered()
        {

            $calvin = $this->createAdmin();
	        $this->withDataInSession(['auth.2fa.challenged_user' => $calvin->ID]);
	        $this->generateTestSecret($calvin);

            $response = $this->get('/auth/two-factor/challenge');

            $response->assertOk();
            $response->assertSee('Code');
            $response->assertSeeHtml('/auth/login');

        }

    }