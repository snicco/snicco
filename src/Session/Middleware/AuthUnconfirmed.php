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

        public function __construct(ResponseFactory $response_factory, $url = 'admin')
        {
            $this->response_factory = $response_factory;
            $this->url = $url;
        }

        public function handle(Request $request, Delegate $next)
        {

            $session = $request->getSession();

            if ( $session->hasValidAuthConfirmToken() ) {


                $url = ( $this->url !== 'admin')
                    ? $this->url
                    : $session->getIntendedUrl ( WP::adminUrl() );;

                return $this->response_factory->redirect()->to($url);

            }

            return $next($request);

        }


    }