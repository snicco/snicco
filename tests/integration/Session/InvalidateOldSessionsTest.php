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

            /** @var RouteRegistrar $registrar */
            $registrar = TestApp::resolve(RouteRegistrarInterface::class);
            $registrar->globalRoutes(TestApp::config());
            $registrar->standardRoutes(TestApp::config());
            $registrar->loadIntoRouter();
        }

        /** @test */
        public function the_session_id_is_regenerated_on_a_login_event () {


            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->testSessionId(), serialize(['foo' => 'bar']));

            $request = TestRequest::fromFullUrl('GET', 'https://wpemerge.test/wp-login.php?action=login');
            $request = $request->withAddedHeader('Cookie', 'wp_mvc_session='.$this->testSessionId() );
            $this->rebindRequest($request);

            ob_start();
            do_action('init');
            do_action('wp_login');
            $this->assertSame('', ob_get_clean());


            $id_after_login = $session->getId();

            // Session Id not the same
            $this->assertNotSame($this->testSessionId(), $id_after_login);
            HeaderStack::assertContains('Set-Cookie', $id_after_login);

            // Data is not in the handler anymore
            $data = unserialize($array_handler->read($id_after_login));
            $this->assertSame('bar', $data['foo']);

            // The old session is gone.
            $this->assertSame('', $array_handler->read($this->testSessionId()));

        }

    }