<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use Carbon\Carbon;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Traits\HasLottery;
    use WPEmerge\Session\Session;

    class SessionManager
    {

        use HasLottery;

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

        public function start(Request $request) : Session
        {

            $cookies = $request->cookies();
            $cookie_name = $this->config['cookie'];
            $session_id = $cookies->get($cookie_name, '');
            $this->session->start($session_id);
            $this->session->getDriver()->setRequest($request);

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

        /**
         *
         * NOTE: We are not in a routing flow. This method gets called on the wp_login
         * event.
         *
         * @param  Request  $request
         * @param  ResponseEmitter  $emitter
         */
        public function migrateAfterLogin(Request $request, ResponseEmitter $emitter)
        {

            $this->start($request);

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
         * @param  Request  $request
         * @param  ResponseEmitter  $emitter
         */
        public function invalidateAfterLogout(Request $request, ResponseEmitter $emitter)
        {

            $this->start($request);

            $this->session->invalidate();
            $this->session->save();

            $cookies = new Cookies();
            $cookies->add($this->sessionCookie());

            $emitter->emitCookies($cookies);

        }


    }