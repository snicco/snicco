<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Support\WP;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Routing\UrlGenerator;

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