<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Middleware;

    use Carbon\Carbon;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Session\Session;

    class AuthUnconfirmed extends Middleware
    {


        /**
         * @var string
         */
        private $url;

        public function __construct( $url = 'admin')
        {
            $this->url = $url;
        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            $session = $request->session();

            if ( $session->hasValidAuthConfirmToken() ) {


                $url = ( $this->url !== 'admin')
                    ? $this->url
                    : $session->getIntendedUrl ( WP::adminUrl() );;

                return $this->response_factory->redirect()->to($url);

            }

            return $next($request);

        }


    }