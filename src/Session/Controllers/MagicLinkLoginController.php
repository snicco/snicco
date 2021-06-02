<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Carbon\Carbon;
    use WP_User;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\SessionStore;

    class MagicLinkLoginController
    {

        /**
         * @var SessionStore
         */
        private $session_store;

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        protected $lifetime_in_minutes = 180;

        public function __construct(SessionStore $session_store, ResponseFactory $response_factory)
        {
            $this->session_store = $session_store;
            $this->response_factory = $response_factory;
        }

        public function create(Request $request, string $user_id) : RedirectResponse
        {

            WP::logout();

            $user = get_user_by('ID', (int) $user_id);

            if ( ! $user instanceof WP_User ) {

                return $this->response_factory->redirect(404)->to(WP::loginUrl());

            }

            $intended_url = $this->intendedUrl($request);

            $this->loginUser($user);

            $this->setTemporaryAuthToken();

            return $this->response_factory
                ->redirect(200)
                ->to($intended_url);

        }

        private function setTemporaryAuthToken()
        {

            $this->session_store->put(
                'auth.confirm.until',
                Carbon::now()->addMinutes($this->lifetime_in_minutes)->getTimestamp()
            );

        }

        private function loginUser(WP_User $user)
        {

            $this->session_store->migrate(true);

            wp_set_auth_cookie($user->ID, true, true );
            wp_set_current_user($user->ID);


        }

        private function intendedUrl (Request $request) {

            $from_query = rawurldecode($request->getQueryString('intended', ''));

            if ($from_query !== '') {
                return $from_query;
            }

            $from_session = $this->session_store->get('auth.confirm.intended_url', '');

            if ($from_session !== '') {
                return $from_session;
            }

            return WP::adminUrl();

        }

    }