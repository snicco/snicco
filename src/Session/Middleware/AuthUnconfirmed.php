<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Middleware;

    use Carbon\Carbon;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Session;

    class AuthUnconfirmed extends Middleware
    {

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var string
         */
        private $url;

        /**
         * @var Session
         */
        private $session;

        public function __construct(ResponseFactory $response_factory, Session $session, $url = 'admin')
        {
            $this->response_factory = $response_factory;
            $this->url = $url;
            $this->session = $session;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( $this->hasValidAuthToken() ) {

                $url = $this->session->get('auth.confirm.intended_url', WP::adminUrl());

                $url = ( $this->url !== 'admin') ? $this->url : $url;

                return $this->response_factory->redirect(200)->to($url);

            }

            return $next($request);

        }

        private function hasValidAuthToken () : bool
        {

            return Carbon::now()->getTimestamp() < $this->session->get('auth.confirm.until', 0);

        }

    }