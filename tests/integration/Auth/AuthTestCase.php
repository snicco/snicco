<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\TestCase;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Validation\ValidationServiceProvider;

    class AuthTestCase extends TestCase
    {

        public function packageProviders() : array
        {

            return [
                ValidationServiceProvider::class,
                SessionServiceProvider::class,
                AuthServiceProvider::class
            ];
        }

    }