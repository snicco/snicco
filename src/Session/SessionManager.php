<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Carbon\Carbon;
    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Auth\WpAuthSessionToken;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Events\SessionRegenerated;
    use WPEmerge\Traits\HasLottery;

    class SessionManager implements SessionManagerInterface
    {

        use HasLottery;
        use InteractsWithTime;

        public const DAY_IN_SEC        = 86400;
        public const HOUR_IN_SEC       = 3600;
        public const THIRTY_MIN_IN_SEC = 1800;
        public const WEEK_IN_SEC       = self::DAY_IN_SEC * 7;

        /**
         * @var array
         */
        private $config;

        /**
         * @var Session
         */
        private $session;

        public function __construct(array $session_config, Session $session)
        {

            $this->config = $session_config;
            $this->session = $session;

        }

        public function start(Request $request, int $user_id) : Session
        {

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
                   ->expires($this->session->absoluteTimeout())
                   ->onlyHttp()
                   ->domain($this->config['domain']);

            return $cookie;

        }

        public function save()
        {

            if ($this->session->rotationDueAt() === 0) {

                $this->session->setNextRotation($this->rotationInterval());

            }

            if ($this->session->absoluteTimeout() === 0) {

                $this->session->setAbsoluteTimeout($this->maxSessionLifetime());

            }

            if ($this->needsRotation()) {

                $this->session->regenerate();
                $this->session->setNextRotation($this->rotationInterval());
                SessionRegenerated::dispatch([$this->session]);

            }

            $this->session->save();

        }

        public function collectGarbage()
        {

            if ($this->hitsLottery($this->config['lottery'])) {

                $this->session->getDriver()->gc($this->maxSessionLifetime());

            }

        }

        private function maxSessionLifetime()
        {

            return $this->config['lifetime'];

        }

        private function needsRotation() : bool
        {

            if ( ! isset($this->config['rotate']) || ! is_int($this->config['rotate'])) {
                return false;
            }

            $rotation = $this->session->rotationDueAt();

            return $this->currentTime() - $rotation > 0;

        }

        private function rotationInterval() : int
        {

            return $this->config['rotate'];
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

            $this->start($request, $request->userId());

            $this->session->regenerate();
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

            $this->start($request, $request->userId());

            $this->session->invalidate();
            $this->session->save();

            $cookies = new Cookies();
            $cookies->add($this->sessionCookie());

            $emitter->emitCookies($cookies);

        }


    }