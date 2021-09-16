<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Contracts\AbstractRedirector;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Auth\Contracts\AbstractLoginResponse;

class RedirectToDashboardResponse extends AbstractLoginResponse
{
    
    private AbstractRedirector $redirector;
    
    public function __construct(AbstractRedirector $redirector)
    {
        $this->redirector = $redirector;
    }
    
    public function toResponsable() :RedirectResponse
    {
        return $this->redirector->toRoute('dashboard');
    }
    
}