<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use PHPUnit\Framework\Assert as PHPUnit;
    use Tests\helpers\HashesSessionIds;
    use WPEmerge\Application\Application;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Arr;

    /**
     * @property Session $session
     * @property Application $app
     * @property array $default_headers
     * @property array $default_cookies
     */
    trait InteractsWithSession
    {

        use HashesSessionIds;

        protected $session_id;

        /**
         * @param  array  $data Keys are expected to be in dot notation
         */
        protected function withDataInSession(array $data, string $id = null )
        {

            $to_driver = [];
            foreach ($data as $key => $value ) {

                Arr::set($to_driver, $key, $value);
                $this->session->put($key, $value);
            }

            $write_to = $id ? $this->hash($id) : $this->hash($this->testSessionId());

            $this->sessionDriver()->write($write_to , serialize($to_driver));

            return $this;

        }

        protected function withSessionId( string $id ) {

            $this->session_id = $id;
            return $this;

        }

        protected function sessionDriver() :SessionDriver {
            return $this->app->resolve(SessionDriver::class);
        }

        protected function testSessionId () {

            return $this->session_id ?? str_repeat('a', 64);

        }

        protected function withSessionCookie(string $name = 'wp_mvc_session') {

            $this->default_cookies[$name] = $this->testSessionId();
            return $this;

        }

        protected function assertDriverHas($expected, string $key, $id = null ) {

            $data = unserialize($this->sessionDriver()->read($this->hash($id ?? $this->testSessionId())));

            PHPUnit::assertSame($expected, Arr::get($data, $key, 'null'), "The session driver does not have the correct value for [$key]");

        }

        protected function assertDriverNotHas(string $key, $id = null ) {

            $data = unserialize($this->sessionDriver()->read($this->hash($id ?? $this->testSessionId())));

            PHPUnit::assertNull(Arr::get($data, $key), "Unexpect value in the session driver for [$key]");

        }

        protected function assertDriverEmpty(string $id) {

            PHPUnit::assertSame('', $this->sessionDriver()->read($this->hash($id)), "The session driver is not empty.");

        }

        protected function assertSessionUserId(int $id) {
            PHPUnit::assertSame($id, $this->session->userId());
        }

    }