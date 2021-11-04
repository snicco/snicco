<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Support\WP;
use Snicco\View\ViewFactory;
use Snicco\Auth\Contracts\AbstractRegistrationView;

class EmailRegistrationViewView extends AbstractRegistrationView
{
    
    private ViewFactory $view_factory;
    
    public function __construct(ViewFactory $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toResponsable()
    {
        
        return $this->view_factory->make('framework.auth.registration')->with([
            'title' => 'Register | '.WP::siteName(),
            'post_to' => $this->request->path(),
        ]);
        
    }
    
}