<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Auth\Contracts\LoginResponse;
use Snicco\Contracts\AbstractRedirector;
use Snicco\Http\Responses\RedirectResponse;

class RedirectToDashboardResponse extends LoginResponse
{
    
    private AbstractRedirector $redirector;
    
    public function __construct(AbstractRedirector $redirector)
    {
        $this->redirector = $redirector;
    }
    
    public function toResponsable() :RedirectResponse
    {
        return $this->redirectToDashboard();
    }
    
    private function redirectToDashboard() :RedirectResponse
    {
        return $this->redirector->toRoute('dashboard');
    }
    
}