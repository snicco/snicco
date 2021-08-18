<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\RegistrationViewResponse;

class EmailRegistrationViewResponse extends RegistrationViewResponse
{
    
    private ViewFactory $view_factory;
    
    public function __construct(ViewFactory $view_factory, UrlGenerator $url)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toResponsable()
    {
        
        return $this->view_factory->make('auth-layout')->with([
            'view' => 'auth-registration',
            'post_to' => $this->request->path(),
        ]);
        
    }
    
}