<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\AbstractRegistrationView;

class EmailRegistrationViewView extends AbstractRegistrationView
{
    
    private ViewFactory $view_factory;
    
    public function __construct(ViewFactory $view_factory, UrlGenerator $url)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toResponsable()
    {
        
        return $this->view_factory->make('framework.auth.layout')->with([
            'view' => 'framework.auth.registration',
            'post_to' => $this->request->path(),
        ]);
        
    }
    
}