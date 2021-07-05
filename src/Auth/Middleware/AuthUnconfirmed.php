<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Contracts\Middleware;
    use WPMvc\Support\WP;
    use WPMvc\Http\Delegate;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Routing\UrlGenerator;

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