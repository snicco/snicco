<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\UrlGenerator;

    class AuthUnconfirmed extends Middleware
    {


        /**
         * @var UrlGenerator
         */
        private $url;

        public function __construct( UrlGenerator $url)
        {
            $this->url = $url;
        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            $session = $request->session();

            if ( $session->hasValidAuthConfirmToken() ) {

                return $this->response_factory->back($this->url->toRoute('dashboard'));

            }

            return $next($request);

        }


    }