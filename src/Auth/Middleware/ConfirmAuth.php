<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Session\Session;

    class ConfirmAuth extends Middleware
    {

        public function handle(Request $request, Delegate $next) :ResponseInterface
        {

            $session = $request->session();

            if ( ! $session->hasValidAuthConfirmToken() ) {

                $this->setIntendedUrl($request, $session);

                return $this->response_factory->redirect()->toRoute('auth.confirm');

            }

            return $next($request);

        }

        private function setIntendedUrl(Request $request, Session $session) {

            if ( $request->isGet() && ! $request->isAjax()) {

                $session->setIntendedUrl($request->fullPath());

                return;

            }

            $session->setIntendedUrl($session->getPreviousUrl());

        }

    }