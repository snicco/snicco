<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Session\Session;

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