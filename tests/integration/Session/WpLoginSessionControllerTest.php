<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Carbon\Carbon;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Session\SessionServiceProvider;

    class WpLoginSessionControllerTest extends IntegrationTest
    {

        private function simulateRequest(string $method)
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

            $request = TestRequest::from($method, 'wp-login.php')
                                  ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->sessionId());

            // Simulate current request to /wp-login.php
            TestApp::container()->instance(Request::class, $request);

        }

        private function sessionId() : string
        {

            return str_repeat('a', 40);
        }

        // /** @test */
        public function session_are_migrated_on_login()
        {


            $this->simulateRequest('POST');

            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->sessionId(), serialize(['foo' => 'bar']));

            // The user was logged in by WordPress => run Kernel
            do_action('wp_login');

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame(str_repeat('a', 40), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Content is still in the session
            $data = unserialize($array_handler->read($id_after_login));
            $this->assertContains('bar', $data);

            // The old session is gone.

            $this->assertSame('', $array_handler->read($this->sessionId()));

        }

        // /** @test */
        public function the_auth_confirmed_token_is_set_on_login()
        {

            $this->simulateRequest('POST');


            // The user was logged in by WordPress => run Kernel
            do_action('wp_login');

            $confirmed_until = TestApp::session()->get('auth.confirm.until');

            $this->assertSame(Carbon::now()->addMinutes(180)->getTimestamp(), $confirmed_until, 'Auth confirmed token not set correctly.');


        }

        // /** @test */
        public function auth_confirmation_can_be_disabled_optionally_on_login () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                    'auth_confirm_on_login' => false,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);
            $request = TestRequest::from('POST', 'wp-login.php')
                                  ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->sessionId());

            // Simulate current request to /wp-login.php
            TestApp::container()->instance(Request::class, $request);

            // The user was logged in by WordPress => run Kernel
            do_action('wp_login');


            $this->assertFalse(TestApp::session()->has('auth.confirm.until'));

        }

        // /** @test */
        public function session_are_invalidated_on_logout()
        {

            $this->simulateRequest('GET');

            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->sessionId(), serialize(['foo' => 'bar']));

            // The user was logged out by WordPress => run Kernel
            do_action('wp_logout');

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->sessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is not in the handler anymore
            $data = unserialize($array_handler->read($id_after_login));
            $this->assertNotContains('bar', $data);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->sessionId()));

        }


    }