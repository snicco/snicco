<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use PHPUnit\Framework\Assert as PHPUnit;
    use Tests\helpers\HashesSessionIds;
    use WPEmerge\Application\Application;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\CsrfField;
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

        protected $internal_keys = ['_user', '_url.previous', '_rotate_at', '_expires_at', '_last_activity'];

        private $data_saved_to_driver = false;

        /**
         * @param  array  $data Keys are expected to be in dot notation
         */
        protected function withDataInSession(array $data, string $id = null )
        {

            foreach ($data as $key => $value ) {

                Arr::set($to_driver, $key, $value);
                $this->session->put($key, $value);

            }

            if ( $id ) {
                $this->session_id = $id;
            }

            $write_to = $id ? $this->hash($id) : $this->hash($this->testSessionId());

            // We need to safe at least the session id in the driver so that it does
            // not get invalidated since the framework does not accept session ids
            // that are not in the current driver
            $this->sessionDriver()->write($write_to, serialize([]));
            $this->withSessionCookie();

            return $this;

        }

        protected function withCsrfToken() :array {

            /** @var CsrfField $csrf */
            $csrf = $this->app->resolve(CsrfField::class);
            $csrf_token = $csrf->create();
            $name = $csrf_token['csrf_name'];
            $value = $csrf_token['csrf_value'];

            $this->withDataInSession(["csrf.$name" => $value]);

            return $csrf_token;

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

            $data = $this->sessionDriver()->read($this->hash($id));

            if ( $data === '') {
                return;
            }

            $data = unserialize($data);
            Arr::forget($data, $this->internal_keys);

            PHPUnit::assertEmpty($data['_flash']['old'], "The flash key is not empty for id [$id].");
            PHPUnit::assertEmpty($data['_flash']['new'], "The flash key is not empty for id [$id].");
            PHPUnit::assertEmpty($data['_url'], "The session driver is not empty for id [$id].");

            Arr::forget($data, '_flash');
            Arr::forget($data, '_url');

            $keys = implode(',',array_keys($data));
            PHPUnit::assertEmpty($data,"The session driver is not empty for id [$id]." . PHP_EOL . "Found keys [$keys]");

        }

        protected function assertSessionUserId(int $id) {
            PHPUnit::assertSame($id, $this->session->userId());
        }

    }