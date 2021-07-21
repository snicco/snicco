<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Tests\stubs\TestApp;
    use Snicco\Http\Psr7\Request;
    use Snicco\Session\Session;

    trait InteractsWithSessionDriver
    {

        use HashesSessionIds;

        private function getSession() : Session
        {

            /** @var Session $session */
            $session = TestApp::resolve(Session::class);

            return $session;

        }

        private function writeTokenToSessionDriver(\Carbon\Carbon $carbon)
        {

            $driver = $this->getSession()->getDriver();
            $driver->write($this->hashedSessionId(), serialize([
                'auth' => [
                    'confirm' => [
                        'until' => $carbon->getTimestamp(),
                    ],
                ],
            ]));

        }

        private function writeToDriver(array $attributes)
        {
            $driver = $this->getSession()->getDriver();
            $driver->write($this->hashedSessionId(), serialize($attributes));

        }

        private function addToDriver (array $attributes) {

            $data = unserialize($this->getSession()->getDriver()->read($this->testSessionId()));

            $data = array_merge_recursive($data, $attributes);

            $this->writeToDriver($data);

        }

        private function withSessionCookie( Request $request) {

            $cookie = 'wp_mvc_session='.$this->getSessionId();

            return $request->withAddedHeader('Cookie', $cookie);

        }

        private function readFromDriver(string $session_id ) : string
        {

            return $this->getSession()->getDriver()->read($session_id);

        }

    }