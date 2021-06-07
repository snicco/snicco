<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Session\Exceptions\InvalidCsrfTokenException;
    use WPEmerge\Session\SessionServiceProvider;

    class LogoutControllerTest extends IntegrationTest
    {

        use CreateDefaultWpApiMocks;

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


            $this->createDefaultWpApiMocks();
            WP::shouldReceive('checkAdminReferer')
              ->with('log-out')
              ->andReturnTrue()
              ->byDefault();

        }

        protected function beforeTearDown()
        {

            WP::reset();
            Mockery::close();
        }

        /** @test */
        public function the_route_can_not_be_accessed_without_a_valid_csrf_token() {

            $request = TestRequest::from('GET', '/auth/logout');
            $this->rebindRequest($request);

            $this->expectException(InvalidCsrfTokenException::class);

            do_action('init');


        }

    }
