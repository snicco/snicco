<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Exceptions;

    use Throwable;
    use WPMvc\Http\Psr7\Request;

    class FailedTwoFactorAuthenticationException extends FailedAuthenticationException
    {

        public function __construct($message, Request $request, $code = 0, Throwable $previous = null)
        {
            $this->redirectToRoute('auth.2fa.challenge');
            parent::__construct($message, $request, [], $code, $previous);
        }

        public function report () {

            //

        }

    }