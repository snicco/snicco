<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WP_User;
    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\Traits\ResolveTwoFactorSecrets;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Auth\Contracts\TwoFactorChallengeResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class RedirectIf2FaAuthenticable extends Authenticator
    {

        use ResolveTwoFactorSecrets;


        /**
         * @var TwoFactorChallengeResponse
         */
        private $challenge_response;

        public function __construct(TwoFactorChallengeResponse $response)
        {
            $this->challenge_response = $response;
        }

        public function attempt(Request $request, $next) : Response
        {

            $response = $next($request);

            if ( ! $response instanceof SuccessfulLoginResponse ) {

                return $response;

            }

            if ( ! $this->userHasTwoFactorEnabled( $user = $response->authenticatedUser() ) ) {

                return $response;

            }

            $this->challengeUser($request, $user);

            return $this->challenge_response->setRequest($request)->toResponsable();

        }



        private function challengeUser(Request $request, WP_User $user) : void
        {

            $request->session()->put('2fa.challenged_user', $user->ID);
            $request->session()->put('2fa.remember', $request->boolean('remember_me'));

        }

    }