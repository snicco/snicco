<?php


    declare(strict_types = 1);


    namespace WPEmerge\Middleware;

    use Carbon\Carbon;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\SessionStore;

    class ConfirmAuth extends Middleware
    {

        /**
         * @var SessionStore
         */
        private $session_store;
        /**
         * @var ResponseFactory
         */
        private $response_factory;
        /**
         * @var UrlGenerator
         */
        private $url_generator;

        public function __construct(SessionStore $session_store, ResponseFactory $response_factory, UrlGenerator $url_generator)
        {
            $this->session_store = $session_store;
            $this->response_factory = $response_factory;
            $this->url_generator = $url_generator;
        }

        public function handle(Request $request, Delegate $next)
        {

            if ( ! $this->hasValidAuthToken() ) {

                $this->session_store->invalidate();

                $this->session_store->flash('auth.confirm.intended_url', $request->getFullUrl());

                return $this->response_factory->redirect(401)->to('/auth/confirm');

            }

            return $next($request);

        }

        private function hasValidAuthToken () : bool
        {

            return Carbon::now()->getTimestamp() < $this->session_store->get('auth.confirm.until', 0);

        }


    }