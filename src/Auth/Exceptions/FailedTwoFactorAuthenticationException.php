<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Exceptions;

    use Throwable;

    class FailedTwoFactorAuthenticationException extends FailedAuthenticationException
    {
        public function __construct(string $log_message, array $old_input = [], string $redirect_to = 'auth.2fa.challenge', Throwable $previous = null)
        {
            parent::__construct(
                $log_message,
                $old_input,
                $redirect_to,
                $previous
            );
        }
    
    }