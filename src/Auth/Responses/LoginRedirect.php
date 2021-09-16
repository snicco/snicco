<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Routing\UrlGenerator;
use Snicco\Session\StatefulRedirector;
use Snicco\Http\Responses\RedirectResponse;
use Snicco\Auth\Contracts\AbstractLoginResponse;

class LoginRedirect extends AbstractLoginResponse
{
    
    private StatefulRedirector $redirector;
    private UrlGenerator       $url;
    
    public function __construct(StatefulRedirector $redirector, UrlGenerator $url)
    {
        $this->redirector = $redirector;
        $this->url = $url;
    }
    
    public function toResponsable() :RedirectResponse
    {
        return $this->redirector->intended($this->request, $this->url->toRoute('dashboard'));
    }
    
}