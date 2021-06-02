<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session\Controllers;

    use Carbon\Carbon;
    use WP_User;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\Url;

    class ConfirmAuthMagicLinkController
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
         * @var int|mixed
         */
        private $lifetime_in_minutes;

        public function __construct(SessionStore $session_store, ResponseFactory $response_factory, int $lifetime_in_minutes = 180 )
        {

            $this->session_store = $session_store;
            $this->response_factory = $response_factory;
            $this->lifetime_in_minutes = $lifetime_in_minutes;

        }

        public function create(Request $request, string $user_id) : RedirectResponse
        {

            $user = $this->getUser( ( int ) $user_id);

            if ( ! $user instanceof WP_User ) {

                throw new NotFoundException();

            }

            $this->loginUser($user);

            $this->setTemporaryAuthToken();

            return $this->response_factory
                ->redirect()
                ->to($this->intendedUrl($request));


        }

        private function setTemporaryAuthToken()
        {

            $this->session_store->put(
                'auth.confirm.until',
                Carbon::now()->addMinutes($this->lifetime_in_minutes)->getTimestamp()
            );

        }

        // If the user is logged in already we just refresh the auth cookie.
        private function loginUser(WP_User $user)
        {

            $this->session_store->migrate(true);
            wp_set_auth_cookie($user->ID, true, true);
            wp_set_current_user($user->ID);


        }

        private function intendedUrl(Request $request)
        {

            $from_query = rawurldecode($request->getQueryString('intended', ''));

            if (Url::isValidAbsolute($from_query)) {
                return $from_query;
            }

            $from_session = $this->session_store->get('auth.confirm.intended_url', '');

            if (Url::isValidAbsolute($from_session)) {
                return $from_session;
            }

            return WP::adminUrl();

        }

        private function getUser(int $user_id ) {

            return WP::isUserLoggedIn() ? WP::currentUser() : get_user_by('ID', $user_id);

        }

    }