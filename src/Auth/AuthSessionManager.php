<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth;

    use Illuminate\Support\InteractsWithTime;
    use BetterWP\Http\Cookie;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Session\Session;
    use BetterWP\Session\Contracts\SessionDriver;
    use BetterWP\Session\SessionManager;
    use BetterWP\Session\Contracts\SessionManagerInterface;
    use BetterWP\Support\Arr;
    use BetterWP\Traits\HasLottery;

    class AuthSessionManager implements SessionManagerInterface
    {

        use InteractsWithTime;

        /**
         * @var SessionManager
         */
        private $manager;

        /** @var Session|null */
        private $active_session;

        /**
         * @var  SessionDriver
         */
        private $driver;

        /**
         * @var array
         */
        private $auth_config;

        /** @var callable|null */
        private $idle_resolver;

        public function __construct(SessionManager $manager, SessionDriver $driver, array $auth_config)
        {

            $this->manager = $manager;
            $this->driver = $driver;
            $this->auth_config = $auth_config;
        }

        public function start(Request $request, int $user_id) : Session
        {

            if ($session = $this->activeSession()) {
                return $session;
            }

            $this->active_session = $this->manager->start($request, $user_id);

            return $this->active_session;

        }

        public function setIdleResolver(callable $idle_resolver)
        {

            $this->idle_resolver = $idle_resolver;

        }

        public function sessionCookie() : Cookie
        {

            return $this->manager->sessionCookie();
        }

        public function save()
        {
            $this->manager->save();
        }

        public function collectGarbage()
        {

            $this->manager->collectGarbage();
        }

        public function getAllForUser() : array
        {

            $sessions = $this->active_session->getAllForUser();

            return collect($sessions)
                ->flatMap(function (object $session) {

                    return [$session->id => $session->payload];

                })
                ->filter(function (array $payload) {

                    return $this->valid($payload);

                })
                ->all();

        }

        public function destroyOthersForUser(string $hashed_token, int $user_id)
        {

            $this->driver->destroyOthersForUser($hashed_token, $user_id);

        }

        public function destroyAllForUser(int $user_id)
        {

            $this->driver->destroyAllForUser($user_id);

        }

        public function destroyAll()
        {

            $this->driver->destroyAll();

        }

        public function activeSession() : ?Session
        {

            if ($this->active_session instanceof Session) {
                return $this->active_session;
            }

            return null;

        }

        public function idleTimeout()
        {

            $timeout = Arr::get($this->auth_config, 'idle', 0);

            if (is_callable($this->idle_resolver)) {

                return call_user_func($this->idle_resolver, $timeout);

            }

            return $timeout;

        }

        private function isIdle(array $session_payload) : bool
        {

            $last_activity = $session_payload['_last_activity'] ?? 0;

            return ($this->currentTime() - $last_activity) > $this->idleTimeout();

        }

        private function allowsPersistentLogin() : bool
        {

            return Arr::get($this->auth_config, 'features.remember_me', false) === true;

        }

        private function isExpired(array $session_payload) : bool
        {

            $expires = $session_payload['_expires_at'] ?? 0;

            return $expires < $this->currentTime();

        }

        private function valid(array $session_payload) : bool
        {

            if ($this->isExpired($session_payload)) {

                return false;

            }

            if ($this->isIdle($session_payload) && ! $this->allowsPersistentLogin()) {

                return false;

            }

            return true;

        }

    }