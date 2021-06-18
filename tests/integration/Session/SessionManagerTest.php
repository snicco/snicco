<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Tests\helpers\HashesSessionIds;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Session\SessionServiceProvider;

    class SessionManagerTest extends IntegrationTest
    {

        use HashesSessionIds;

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
            $array_handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));

            $this->rebindRequest(TestRequest::from('GET', 'foo')
                                            ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->getSessionId()));

            ob_start();

            do_action('wp_login');

            $this->assertSame('', ob_get_clean());

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->getSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is for the new id is in the handler.
            $data = unserialize($array_handler->read($this->hash($id_after_login)));
            $this->assertSame('bar', $data['foo']);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->hashedSessionId()));

        }

        /** @test */
        public function session_are_invalidated_on_logout () {

            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));

            $this->rebindRequest(TestRequest::from('GET', 'foo')
                                            ->withAddedHeader('Cookie', 'wp_mvc_session='.$this->getSessionId()));

            ob_start();

            do_action('wp_logout');

            $this->assertSame('', ob_get_clean());

            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->getSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is for the new id is not in the handler.
            $data = unserialize($array_handler->read($this->hash($id_after_login)));
            $this->assertArrayNotHasKey('foo', $data);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->hashedSessionId()));

        }


    }