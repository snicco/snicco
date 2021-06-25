<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WP_User;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\ExceptionHandling\Exceptions\AuthorizationException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Session\Events\SessionRegenerated;
    use WPEmerge\Session\SessionManager;

    class ConfirmAuthMagicLinkController extends Controller
    {

        use ResolvesUser;

        /**
         * @var int|mixed
         */
        private $lifetime_in_seconds;

        public function __construct( int $lifetime_in_seconds = SessionManager::HOUR_IN_SEC * 3 )
        {

            $this->lifetime_in_seconds = $lifetime_in_seconds;

        }

        public function store(Request $request, string $user_id) : RedirectResponse
        {

            $user = $this->getUserById((int)$user_id);

            if ( ! $user instanceof WP_User || $user->ID !== WP::currentUser()->ID ) {

                throw new AuthorizationException();

            }

            $session = $request->session();
            $session->confirmAuthUntil($this->lifetime_in_seconds);
            $session->regenerate();
            SessionRegenerated::dispatch([$session]);

            return $this->response_factory->redirect()
                                          ->intended($request, WP::adminUrl());


        }


    }