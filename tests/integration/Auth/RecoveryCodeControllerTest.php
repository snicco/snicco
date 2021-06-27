<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Illuminate\Support\Collection;
    use Tests\helpers\HashesSessionIds;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\RecoveryCode;
    use WPEmerge\Contracts\EncryptorInterface;
    use WPEmerge\Session\Encryptor;
    use WPEmerge\Session\SessionServiceProvider;

    class RecoveryCodeControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use HashesSessionIds;

        private $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
                'lifetime' => 3600,
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
            'auth' => [

                'confirmation' => [
                    'duration' => 10,
                ],

                'remember' => [
                    'enabled' => false,
                ],
                'features' => [
                    'two-factor-authentication' => true,
                ],
            ],
            'app_key' => TEST_APP_KEY,
        ];

        /**
         * @var string
         */
        private $encrypted_codes;

        /**
         * @var array
         */
        private $codes;

        /**
         * @var string
         */
        private $route_url;

        /**
         * @var Encryptor
         */
        private $encryptor;


        protected function afterSetup()
        {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $this->codes = Collection::times(8, function () {

                return RecoveryCode::generate();

            })->all();
            $this->encryptor = TestApp::resolve(EncryptorInterface::class);
            $this->encrypted_codes = $this->encryptor->encrypt(json_encode($this->codes));
            $this->route_url = TestApp::url()->signedRoute('auth.2fa.recovery-codes');

        }

        // /** @test */
        public function all_recovery_codes_can_be_shown_for_a_user()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encrypted_codes);
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $request = TestRequest::from('GET', $this->route_url);
            $request = $this->withSession($request, [
                '_last_activity' => time(), 'auth.confirm.until' => time() + 10,
            ]);

            $output = $this->runKernel($request);
            HeaderStack::assertHasStatusCode(200);
            $this->assertSame($this->codes, json_decode($output, true)['codes']);

        }

        // /** @test */
        public function recovery_codes_can_be_updated_for_a_user()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);

            update_user_meta($calvin->ID, 'two_factor_recovery_codes', $this->encrypted_codes);
            update_user_meta($calvin->ID, 'two_factor_secret', 'secret');

            $request = TestRequest::from('PUT', $this->route_url)->withParsedBody([
                'csrf_name' => 'secret_csrf_name',
                'csrf_value' => 'secret_csrf_value',
            ]);
            $request = $this->withSession($request,
                [
                    '_last_activity' => time(),
                    'auth.confirm.until' => time() + 10,
                    'csrf.secret_csrf_name' => 'secret_csrf_value',
                ]
            )->withHeader('Accept', 'application/json');

            $output = $this->runKernel($request);
            HeaderStack::assertHasStatusCode(200);
            $this->assertTrue(json_decode($output, true)['success']);

            $codes = get_user_meta($calvin->ID, 'two_factor_recovery_codes',true);
            $codes = json_decode($this->encryptor->decrypt($codes), true);

            $this->assertNotSame($this->codes, $codes);

        }


    }