<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Contracts\CreatesNewUser;
    use WPEmerge\Auth\Contracts\DeletesUsers;
    use WPEmerge\Auth\Events\Registration;
    use WPEmerge\Auth\Responses\CreateAccountViewResponse;
    use WPEmerge\Auth\Responses\RegisteredResponse;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\ExceptionHandling\Exceptions\AuthorizationException;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;

    class AccountController extends Controller
    {

        use ResolvesUser;

        /**
         * @var int
         */
        private $lifetime_in_seconds;

        public function __construct($lifetime_in_seconds = 900)
        {

            $this->lifetime_in_seconds = $lifetime_in_seconds;
        }

        public function create(Request $request, CreateAccountViewResponse $view_response)
        {

            return $view_response->setRequest($request)->postTo(
                $this->url->signedRoute('auth.accounts.store', [], $this->lifetime_in_seconds)
            );
        }

        public function store(Request $request, CreatesNewUser $creates_new_user, RegisteredResponse $response)
        {

            $user = $this->getUserById($creates_new_user->create($request));

            Registration::dispatch([$user]);

            return $response->setRequest($request)->setUser($user);

        }

        public function destroy(Request $request, int $user_id, DeletesUsers $deletes_users)
        {

            $is_admin = $this->isAdmin($request->user());

            if ( ! $is_admin && $user_id !== $request->userId() ) {

                throw new AuthorizationException('You are not allowed to perform this action.');

            }

            if ( $is_admin && $user_id === $request->userId() ) {

                throw new AuthorizationException('You cant delete your own administrator account.');

            }

            wp_delete_user($user_id, $deletes_users->reassign($user_id));

            return $this->response_factory->noContent();

        }


    }