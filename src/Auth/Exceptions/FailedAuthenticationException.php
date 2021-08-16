<?php

declare(strict_types=1);

namespace Snicco\Auth\Exceptions;

use Throwable;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseFactory;
use Snicco\ExceptionHandling\Exceptions\HttpException;

class FailedAuthenticationException extends HttpException
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
    
}