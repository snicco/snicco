<?php

declare(strict_types=1);

namespace Snicco\Auth\Exceptions;

use Throwable;
use Snicco\Support\Str;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Bannable;
use Snicco\Http\ResponseFactory;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class FailedAuthenticationException extends HttpException implements Bannable
{
    
    private array    $old_input;
    protected string $message_for_users = 'We could not authenticate your credentials.';
    private string   $redirect_to;
    
    public function __construct(string $log_message, array $old_input = [], string $redirect_to = 'auth.login', Throwable $previous = null)
    {
        $this->old_input = $old_input;
        $this->redirect_to = $redirect_to;
        parent::__construct(302, $log_message, $previous);
    }
    
    public function render(ResponseFactory $response_factory) :Response
    {
        return $response_factory->redirect()
                                ->toRoute($this->redirect_to)
                                ->withErrors(['login' => $this->message_for_users])
                                ->withInput($this->old_input);
    }
    
    public function priority() :int
    {
        return LOG_WARNING;
    }
    
    public function fail2BanMessage()
    {
        
        if ( ! Str::startsWith($this->getMessage(), 'Failed Authentication')) {
            
            return 'Failed Authentication '.$this->getMessage();
            
        }
        
        return $this->getMessage();
        
    }
    
}