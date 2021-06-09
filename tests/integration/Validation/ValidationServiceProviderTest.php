<?php


    declare(strict_types = 1);


    namespace Tests\integration\Validation;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use WPEmerge\Validation\Middleware\ShareValidatorWithRequest;
    use WPEmerge\Validation\ValidationServiceProvider;
    use WPEmerge\Validation\Validator;

    class ValidationServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function a_validator_can_be_retrieved () {

            $this->newTestApp([
                'providers' => [
                    ValidationServiceProvider::class
                ]
            ]);

            $v = TestApp::resolve(Validator::class);

            $this->assertInstanceOf(Validator::class, $v);

        }

        /** @test */
        public function config_is_bound () {

            $this->newTestApp([
                'providers' => [
                    ValidationServiceProvider::class
                ]
            ]);

            $this->assertSame([], TestApp::config('validation.messages'));

        }

        /** @test */
        public function global_middleware_is_added () {

            $this->newTestApp([
                'providers' => [
                    ValidationServiceProvider::class
                ]
            ]);

            $middleware = TestApp::config('middleware');

            $this->assertContains(ShareValidatorWithRequest::class, $middleware['groups']['global']);
            $this->assertContains(ShareValidatorWithRequest::class, $middleware['unique']);



        }

    }