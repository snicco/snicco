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
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Url;

    class ConfirmAuthMagicLinkController
    {

        /**
         * @var ResponseFactory
         */
        private $response_factory;

        /**
         * @var int|mixed
         */
        private $lifetime_in_minutes;

        public function __construct(ResponseFactory $response_factory, int $lifetime_in_minutes = 180)
        {

            $this->response_factory = $response_factory;
            $this->lifetime_in_minutes = $lifetime_in_minutes;

        }

        public function create(Request $request, string $user_id) : RedirectResponse
        {

            $user = $this->getUser(( int ) $user_id);

            if ( ! $user instanceof WP_User) {

                throw new NotFoundException();

            }

            $this->loginUser($user);

            $session = $request->session();
            $session->migrate(true);
            $session->confirmAuthUntil($this->lifetime_in_minutes);

            return $this->response_factory->redirect()
                                          ->intended($request, WP::adminUrl());


        }

        // If the user is logged in already we just refresh the auth cookie.
        private function loginUser(WP_User $user)
        {

            wp_set_auth_cookie($user->ID, true, true);
            wp_set_current_user($user->ID);


        }

        private function getUser(int $user_id)
        {

            return WP::isUserLoggedIn() ? WP::currentUser() : get_user_by('ID', $user_id);

        }

    }