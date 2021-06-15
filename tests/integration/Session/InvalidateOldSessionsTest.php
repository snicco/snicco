<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Session\SessionServiceProvider;

    class InvalidateOldSessionsTest extends IntegrationTest
    {

        protected function afterSetup()
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
        public function the_session_id_is_regenerated_on_a_login_event () {


            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->testSessionId(), serialize(['foo' => 'bar']));

            $this->rebindRequest(TestRequest::from('GET', 'foo')
                                            ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->testSessionId()));

            ob_start();

            do_action('wp_login');

            $this->assertSame('', ob_get_clean());

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->testSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is for the new id is in the handler.
            $data = unserialize($array_handler->read($id_after_login));
            $this->assertSame('bar', $data['foo']);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->testSessionId()));

        }

    }