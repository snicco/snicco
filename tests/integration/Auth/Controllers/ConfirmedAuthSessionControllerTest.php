<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth\Controllers;

    use Tests\AuthTestCase;
    use Snicco\Events\Event;
    use Snicco\Http\Psr7\Request;
    use Snicco\Auth\Contracts\AuthConfirmation;
    use Snicco\Session\Events\SessionRegenerated;
    use Snicco\Auth\Exceptions\FailedAuthConfirmationException;

    class ConfirmedAuthSessionControllerTest extends AuthTestCase
    {

        private string $endpoint = '/auth/confirm';
        private string $valid_secret_to_confirm = 'bypass';

        protected function setUp() : void
        {
            $this->afterApplicationCreated(function () {

                $this->instance(AuthConfirmation::class, new TestAuthConfirmation());
            });
            parent::setUp();
        }

        /** @test */
        public function the_route_cant_be_accessed_as_a_guest()
        {

            $response = $this->get($this->endpoint);

            $response->assertRedirectPath('/auth/login');

        }

        /** @test */
        public function the_route_cant_be_accessed_with_auth_already_confirmed()
        {

            $this->actingAs($this->createAdmin());

            $response = $this->get($this->endpoint);

            $response->assertRedirectToRoute('dashboard');

        }

        /** @test */
        public function the_confirmation_view_can_be_rendered()
        {

            $this->authenticateAndUnconfirm($this->createAdmin());

            $response = $this->get($this->endpoint);

            $response->assertOk();
            $response->assertSee('[Test] Confirm your authentication.');

        }

        /** @test */
        public function invalid_auth_confirmation_does_not_work()
        {
    
            $this->authenticateAndUnconfirm($this->createAdmin());
            $token = $this->withCsrfToken();
    
            $response = $this->post(
                $this->endpoint,
                $token + [
                    'secret' => 'bogus',
                ]
            );
    
            $response->assertRedirectToRoute('auth.confirm');
            $response->assertSessionHasErrors('auth.confirmation');
            $this->assertFalse($response->session()->hasValidAuthConfirmToken());
    
        }

        /** @test */
        public function invalid_auth_confirmation_does_not_work_json()
        {
        
            $this->authenticateAndUnconfirm($this->createAdmin());
            $token = $this->withCsrfToken();
        
            $response = $this->post(
                $this->endpoint,
                $token + [
                    'secret' => 'bogus',
                ],
                ['Accept' => 'application/json']
            );
        
            $response->assertStatus(200)
                     ->assertExactJson(
                         ['message' => 'We could not authenticate you with the provided credentials.']
                     );
        
            $this->assertFalse($response->session()->hasValidAuthConfirmToken());
        
        }
    
        /** @test */
        public function the_appropriate_exception_is_thrown_on_failure()
        {
        
            $this->withoutExceptionHandling();
            $this->authenticateAndUnconfirm($this->createAdmin());
            $token = $this->withCsrfToken();
        
            $exception_caught = false;
        
            try {
            
                $response = $this->post(
                    $this->endpoint,
                    $token + [
                        'secret' => 'bogus',
                    ]
                );
            
            } catch (FailedAuthConfirmationException $exception) {
            
                $exception_caught = true;
            
                $this->assertStringStartsWith(
                    'Failed auth confirmation',
                    $exception->fail2BanMessage()
                );
            
            }
        
            $this->assertTrue($exception_caught);
            $this->assertFalse($this->session->hasValidAuthConfirmToken());
        
        }
    
        /** @test */
        public function auth_can_be_confirmed()
        {
        
            Event::fake([SessionRegenerated::class]);
            $this->authenticateAndUnconfirm($this->createAdmin());
            $token = $this->withCsrfToken();
            $id_before_confirmation = $this->testSessionId();

            $response = $this->post($this->endpoint, $token + [
                    'secret' => $this->valid_secret_to_confirm,
                ]);

            $response->assertRedirectToRoute('dashboard');


            $this->assertTrue($this->session->hasValidAuthConfirmToken());
            $this->travelIntoFuture(10);
            $this->assertFalse($this->session->hasValidAuthConfirmToken());

            $this->assertNotSame($id_before_confirmation, $response->session()->getId());
            Event::assertDispatched(SessionRegenerated::class);


        }

        /** @test */
        public function auth_confirmation_for_json_requests_just_returns_a_200_code () {

            $this->authenticateAndUnconfirm($this->createAdmin());
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token + [
                    'secret' => $this->valid_secret_to_confirm
                ], ['Accept' => 'application/json']);

            $response->assertStatus(200);

        }

        /** @test */
        public function the_auth_confirm_space_in_the_session_is_cleared_before_confirmation () {

            $this->authenticateAndUnconfirm($this->createAdmin());
            $this->withDataInSession(['auth.confirm.foo' => 'bar']);
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token + [
                    'secret' => $this->valid_secret_to_confirm,
                ]);

            $response->assertRedirectToRoute('dashboard');
            $response->assertSessionMissing('auth.confirm.foo');

        }

        /** @test */
        public function the_user_is_redirected_to_the_intended_url_if_present () {

            $this->authenticateAndUnconfirm($this->createAdmin());
            $this->session->setIntendedUrl('/foo/bar');
            $token = $this->withCsrfToken();

            $response = $this->post($this->endpoint, $token + [
                    'secret' => $this->valid_secret_to_confirm,
                ]);

            $response->assertRedirect('/foo/bar');

        }

    }


    class TestAuthConfirmation implements AuthConfirmation
    {
    
        public function confirm(Request $request) :bool
        {
        
            if ($request->input('secret') === 'bypass') {
            
                return true;
            
            }
        
            return false;

        }

        public function viewResponse(Request $request)
        {

            return '[Test] Confirm your authentication.';
        }

    }