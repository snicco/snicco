<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Middleware;

    use Carbon\Carbon;
    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Auth\Exceptions\TooManyFailedAuthConfirmationsException;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Contracts\Middleware;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Session;

    class ConfirmAuth extends Middleware
    {


        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            $session = $request->session();

            if ( ! $session->hasValidAuthConfirmToken() ) {

                $this->setIntendedUrl($request, $session);

                return $this->response_factory->redirect()->toRoute('auth.confirm.show');

            }

            return $next($request);

        }

        private function setIntendedUrl(Request $request, Session $session) {

            if ( $request->isGet() && ! $request->isAjax()) {

                $session->setIntendedUrl($request->fullUrl());

                return;

            }

            $session->setIntendedUrl($session->getPreviousUrl());

        }


    }