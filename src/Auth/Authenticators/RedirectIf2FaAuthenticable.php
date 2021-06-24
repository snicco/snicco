<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Authenticators;

    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Contracts\TwoFactorAuthenticationProvider;
    use WPEmerge\Auth\ResolveTwoFactorSecrets;
    use WPEmerge\Auth\Responses\SuccesfullLoginResponse;
    use WPEmerge\Auth\Responses\TwoFactorChallengeResponse;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;

    class RedirectIf2FaAuthenticable extends Authenticator
    {

        use ResolveTwoFactorSecrets;

        /**
         * @var TwoFactorAuthenticationProvider
         */
        private $provider;

        /**
         * @var TwoFactorChallengeResponse
         */
        private $challenge_response;

        public function __construct(TwoFactorAuthenticationProvider $provider, TwoFactorChallengeResponse $response)
        {
            $this->provider = $provider;
            $this->challenge_response = $response;
        }

        public function attempt(Request $request, $next) : Response
        {

            $response = $next($request);

            if ( ! $response instanceof SuccesfullLoginResponse ) {

                return $response;

            }

            if ( ! $this->userHasTwoFactorEnabled($response->authenticatedUser() ) ) {

                return $response;

            }

            $this->challengeUser($request);

            return $this->challenge_response->setRequest($request)->toResponsable();

        }

        private function userHasTwoFactorEnabled(\WP_User $user) : bool
        {

            return $this->twoFactorSecret($user->ID) !== '';

        }

        private function challengeUser(Request $request) : void
        {

            $request->session()->put('2fa.challenged_user', 1);
            $request->session()->put('2fa.remember', $request->boolean('remember_me'));
        }

    }