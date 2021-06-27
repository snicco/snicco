<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\Events\SessionRegenerated;

    class ConfirmedAuthSessionController extends Controller
    {

        /**
         * @var AuthConfirmation
         */
        private $auth_confirmation;

        /**
         * @var int
         */
        private $duration;

        public function __construct(AuthConfirmation $auth_confirmation, int $confirmation_duration )
        {
            $this->auth_confirmation = $auth_confirmation;
            $this->duration = $confirmation_duration;
        }

        public function create( Request $request )
        {

            return $this->auth_confirmation->prepare($request)->viewResponse($request);

        }

        public function store(Request $request ) : RedirectResponse
        {

            $confirmed = $this->auth_confirmation->confirm($request);

            if ( $confirmed !== true ) {

                return $this->response_factory->redirect()->toRoute('auth.confirm')->withErrors($confirmed);

            }

            $session = $request->session();
            $session->forget('auth.confirmed');
            $session->confirmAuthUntil($this->duration);
            $session->regenerate();
            SessionRegenerated::dispatch([$request->session()]);

            return $this->response_factory->redirect()->intended($request, $this->url->toRoute('dashboard'));

        }

        public function destroy(Request $request) {

            if ( $request->hasSession() ) {

                $session = $request->session();

                $session->forget('auth.confirmed');
                $session->regenerate();
                SessionRegenerated::dispatch([$session]);

            }

            return $request->isExpectingJson()
                ? $this->response_factory->json(['success' => true, 'message' => 'Auth confirmation revoked.'])
                : $this->response_factory->back()->with('auth_confirmation_deleted', true);


        }

    }