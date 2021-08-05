<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Authenticators;

    use Snicco\Auth\Contracts\Authenticator;
    use Snicco\Auth\Contracts\TwoFactorChallengeResponse;
    use Snicco\Auth\Responses\SuccessfulLoginResponse;
    use Snicco\Auth\Traits\InteractsWithTwoFactorSecrets;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;
    use WP_User;

    class RedirectIf2FaAuthenticable extends Authenticator
    {

        use InteractsWithTwoFactorSecrets;

        private TwoFactorChallengeResponse $challenge_response;

        public function __construct(TwoFactorChallengeResponse $response)
        {

            $this->challenge_response = $response;
        }

        public function attempt(Request $request, $next) : Response
        {

            $response = $next($request);

            if ( ! $response instanceof SuccessfulLoginResponse) {

                return $response;

            }

            if ( ! $this->userHasTwoFactorEnabled($user = $response->authenticatedUser())) {

                return $response;

            }

            $this->challengeUser($request, $user);

            return $this->response_factory->toResponse(
                $this->challenge_response->forRequest($request)->toResponsable()
            );

        }

        private function challengeUser(Request $request, WP_User $user) : void
        {

            $request->session()->put('auth.2fa.challenged_user', $user->ID);
            $request->session()->put('auth.2fa.remember', $request->boolean('remember_me'));

        }

    }