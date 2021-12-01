<?php

declare(strict_types=1);

namespace Snicco\Http\Responses;

use LogicException;
use Snicco\Session\Session;
use Snicco\Http\Psr7\Response;
use Snicco\Support\MessageBag;
use Illuminate\Contracts\Support\MessageProvider;

class RedirectResponse extends Response
{
    
    /**
     * @var Session|null
     */
    private $session;
    
    /**
     * @var bool
     */
    private $bypass_validation = false;
    
    public function to(string $url) :RedirectResponse
    {
        return $this->withHeader('Location', $url);
    }
    
    public function withSession(Session $session) :RedirectResponse
    {
        $this->session = $session;
        return $this;
    }
    
    /**
     * @param  string|array  $key
     * @param  mixed  $value
     *
     * @return $this
     */
    public function with($key, $value = null) :RedirectResponse
    {
        $key = is_array($key) ? $key : [$key => $value];
        
        foreach ($key as $k => $v) {
            $this->session->flash($k, $v);
        }
        
        return $this;
    }
    
    public function withInput(array $input) :RedirectResponse
    {
        $this->checkSession();
        
        $this->session->flashInput($input);
        
        return $this;
    }
    
    public function hasSession() :bool
    {
        return $this->session instanceof Session;
    }
    
    /**
     * Flash a container of errors to the session.
     *
     * @param  \Snicco\Session\MessageBag|array  $provider
     */
    public function withErrors($provider, string $bag = 'default') :RedirectResponse
    {
        $this->checkSession();
        
        $this->session->withErrors($provider, $bag);
        
        return $this;
    }
    
    public function canBypassValidation() :bool
    {
        return $this->bypass_validation;
    }
    
    public function bypassValidation() :RedirectResponse
    {
        $this->bypass_validation = true;
        return $this;
    }
    
    private function checkSession()
    {
        if ( ! $this->hasSession()) {
            $called_method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
            
            throw new LogicException(
                "The method: [RedirectResponse::{$called_method}] can only be used if session are enabled in the config."
            );
        }
    }
    
    private function toMessageBag($provider) :MessageBag
    {
        if ($provider instanceof MessageProvider) {
            return $provider->getMessageBag();
        }
        
        return new MessageBag((array) $provider);
    }
    
}
