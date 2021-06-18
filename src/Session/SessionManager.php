<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Carbon\Carbon;
    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Support\VariableBag;
    use WPEmerge\Traits\HasLottery;
    use WPEmerge\Session\Session;

    class SessionManager
    {

        use HasLottery;
        use InteractsWithTime;

        public const DAY_IN_SEC        = 86400;
        public const HOUR_IN_SEC       = 3600;
        public const THIRTY_MIN_IN_SEC = 1800;

        /**
         * @var array
         */
        private $config;

        /**
         * @var Session
         */
        private $session;

        /**
         * @var string
         */
        private $cookies;

        /**
         * @var SessionDriver
         */
        private $driver;

        public function __construct(array $session_config, Session $session)
        {

            $this->config = $session_config;
            $this->session = $session;
            $this->driver = $this->driver();

        }

        public function activeSession() : Session
        {

            if ($this->session->isStarted()) {
                return $this->session;
            }

        }

        /**
         *
         * NOTE: We are not in a routing flow. This method gets called on the wp_login
         * event.
         *
         * The Event Listener for this method gets unhooked when using the AUTH-Extension
         *
         * @param  Request  $request
         * @param  ResponseEmitter  $emitter
         */
        public function migrateAfterLogin(Request $request, ResponseEmitter $emitter)
        {

            $this->start($request, $request->user());

            $this->session->migrate(true);
            $this->session->save();

            $cookies = new Cookies();
            $cookies->add($this->sessionCookie());

            $emitter->emitCookies($cookies);

        }

        /**
         *
         * NOTE: We are not in a routing flow. This method gets called on the wp_login/logout
         * event.
         *
         * The Event Listener for this method gets unhooked when using the AUTH-Extension
         *
         * @param  Request  $request
         * @param  ResponseEmitter  $emitter
         */
        public function invalidateAfterLogout(Request $request, ResponseEmitter $emitter)
        {

            $this->start($request,$request->user());

            $this->session->invalidate();
            $this->session->save();

            $cookies = new Cookies();
            $cookies->add($this->sessionCookie());

            $emitter->emitCookies($cookies);

        }

        public function start(Request $request, int $user_id) : Session
        {

            if ($this->session->isStarted()) {
                return $this->session;
            }

            $cookie_name = $this->config['cookie'];
            $session_id = $request->cookies()->get($cookie_name, '');
            $this->session->start($session_id);
            $this->session->setUserId($user_id);

            return $this->session;

        }

        public function sessionCookie() : Cookie
        {

            $cookie = new Cookie($this->config['cookie'], $this->session->getId());

            $cookie->path($this->config['path'])
                   ->sameSite($this->config['same_site'])
                   ->expires(Carbon::now()->addMinutes($this->config['lifetime']))
                   ->onlyHttp()
                   ->domain($this->config['domain']);

            return $cookie;

        }

        public function driver() : SessionDriver
        {

            return $this->session->getDriver();
        }

        public function collectGarbage()
        {

            if ($this->hitsLottery($this->config['lottery'])) {

                $this->session->getDriver()->gc($this->sessionLifetimeInSeconds());

            }
        }

        private function sessionLifetimeInSeconds()
        {

            return $this->config['lifetime'] * 60;
        }

        public function save()
        {

            $this->session->lastActivity($this->currentTime());

            if ( ! $this->session->rotateAt()) {

                $this->setRotation();

            }

            if ( ! $this->session->expiresAt()) {

                $this->setExpiration();

            }

            $this->session->save();

        }

        private function needsRotation() : bool
        {

            return $this->session->rotateAt() !== 0 && $this->currentTime() > $this->session->rotateAt();
        }

        private function setRotation()
        {

            $this->session->rotateAt($this->config['rotate'] ?? static::HOUR_IN_SEC);

        }

        private function setExpiration()
        {

            $this->session->expiresAt($this->config['lifetime'] ?? static::DAY_IN_SEC);


        }

        private function isIdle() : bool
        {

            if ($this->session->lastActivity() === 0) {
                return false;
            }

            $idle_period = $this->config['idle'] ?? static::THIRTY_MIN_IN_SEC;

            return $this->session->lastActivity() + $idle_period < $this->currentTime();
        }

        private function isAbsoluteTimeout() : bool
        {

            return $this->session->expiresAt() !== 0 && $this->currentTime() > $this->session->expiresAt();


        }

        public function getAllForUser() : array
        {

            $sessions = $this->session->getAllForUser();

            $sessions = collect($sessions)
                ->flatMap(function (object $session) {

                    return [$session->id => $session->payload];

                })
                ->filter(function (array $payload) {

                    return $this->isValid($payload);

                })->all();

           return $sessions;


        }

        private function isValid(array $payload)
        {

            return true;

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


    }