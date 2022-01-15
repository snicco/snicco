<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\SessionBundle\StatefulRedirector;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Component\HttpRouting\Http\Responses\RedirectResponse;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

class LoginRedirect extends AbstractLoginResponse
{
    
    private StatefulRedirector   $redirector;
    private InternalUrlGenerator $url;
    
    public function __construct(StatefulRedirector $redirector, InternalUrlGenerator $url)
    {
        $this->redirector = $redirector;
        $this->url = $url;
    }
    
    public function toResponsable() :RedirectResponse
    {
        return $this->redirector->intended($this->request, $this->url->toRoute('dashboard'));
    }
    
}