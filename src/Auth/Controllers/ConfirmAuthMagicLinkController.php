<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WP_User;
    use WPEmerge\ExceptionHandling\Exceptions\AuthorizationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;

    class ConfirmAuthMagicLinkController extends Controller
    {

        /**
         * @var int|mixed
         */
        private $lifetime_in_minutes;

        public function __construct( int $lifetime_in_minutes = 180)
        {

            $this->lifetime_in_minutes = $lifetime_in_minutes;

        }

        public function store(Request $request, string $user_id) : RedirectResponse
        {

            $user = get_user_by('ID', (int) $user_id);

            if ( ! $user instanceof WP_User || $user->ID !== WP::currentUser()->ID ) {

                throw new AuthorizationException();

            }

            $session = $request->session();
            $session->confirmAuthUntil($this->lifetime_in_minutes);
            $session->migrate(true);

            wp_set_auth_cookie($user_id, true, true, $session->getId());

            return $this->response_factory->redirect()
                                          ->intended($request, WP::adminUrl());


        }


    }