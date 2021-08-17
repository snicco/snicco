<?php

namespace Snicco\Auth\Exceptions;

use Throwable;
use Snicco\Support\Str;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Bannable;
use Snicco\Http\ResponseFactory;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class FailedAuthConfirmationException extends HttpException implements Bannable
{
    
    protected string $message_for_users = 'We could not authenticate you with the provided credentials.';
    private string   $redirect_to;
    
    public function __construct(string $log_message, string $redirect_to = 'auth.login', Throwable $previous = null)
    {
        $this->redirect_to = $redirect_to;
        parent::__construct(302, $log_message, $previous);
    }
    
    public function render(ResponseFactory $response_factory) :Response
    {
        return $response_factory->redirect()
                                ->toRoute($this->redirect_to)
                                ->withErrors(['auth.confirmation' => $this->message_for_users]);
    }
    
    public function priority() :int
    {
        return E_WARNING;
    }
    
    public function fail2BanMessage()
    {
        if ( ! Str::startsWith($this->getMessage(), 'Failed auth confirmation')) {
            
            return "Failed auth confirmation {$this->getMessage()}";
            
        }
        
        return $this->getMessage();
        
    }
    
}