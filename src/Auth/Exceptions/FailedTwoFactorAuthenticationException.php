<?php

declare(strict_types=1);

namespace Snicco\Auth\Exceptions;

use Throwable;
use Snicco\Support\Str;

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
    
    public function fail2BanMessage() :string
    {
    
        if ( ! Str::startsWith($this->getMessage(), 'Failed two-factor authentication')) {
        
            return 'Failed two-factor authentication '.$this->getMessage();
        
        }
    
        return $this->getMessage();
        
    }
    
}