<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Listeners;


    use Carbon\Carbon;
    use WPEmerge\Http\Cookie;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Session;

    class InvalidateOldSessions
    {

        /**
         * @var array
         */
        private $config;


        public function __construct(array $session_config)
        {

            $this->config = $session_config;
        }

        /**
         *
         * The session is initialized in the StartSessionMiddleware that always runs
         * for the wp-login.php path
         *
         * @param  Session  $current_session
         * @param  ResponseEmitter  $emitter
         */
        public function handleEvent( Session $current_session, ResponseEmitter $emitter)
       {


           $current_session->migrate(true);
           $current_session->save();


           $cookie = new Cookie($this->config['cookie'], $current_session->getId());
           $cookie->setProperties([
               'path' => $this->config['path'],
               'samesite' => ucfirst($this->config['same_site']),
               'expires' => Carbon::now()->addMinutes($this->config['lifetime'])->getTimestamp(),
               'httponly' => $this->config['http_only'],
               'secure' => $this->config['secure'],
               'domain' => $this->config['domain']

           ]);

           $cookies = new Cookies();
           $cookies->add($cookie);

           $emitter->emitCookies($cookies);



       }


    }