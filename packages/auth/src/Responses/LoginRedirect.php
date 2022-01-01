<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Core\Routing\Internal\Generator;
use Snicco\SessionBundle\StatefulRedirector;
use Snicco\Auth\Contracts\AbstractLoginResponse;
use Snicco\Core\Http\Responses\RedirectResponse;

class LoginRedirect extends AbstractLoginResponse
{
    
    private StatefulRedirector $redirector;
    private Generator          $url;
    
    public function __construct(StatefulRedirector $redirector, Generator $url)
    {
        $this->redirector = $redirector;
        $this->url = $url;
    }
    
    public function toResponsable() :RedirectResponse
    {
        return $this->redirector->intended($this->request, $this->url->toRoute('dashboard'));
    }
    
}