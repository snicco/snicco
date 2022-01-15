<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\Component\Core\Utils\WP;
use Snicco\Auth\Contracts\AbstractRegistrationView;

class EmailRegistrationViewView extends AbstractRegistrationView
{
    
    /**
     * @var ViewEngine
     */
    private $view_factory;
    
    public function __construct(ViewEngine $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function toResponsable()
    {
        return $this->view_factory->make('framework.auth.registration')->with([
            'title' => 'Register | '.WP::siteName(),
            'post_to' => $this->request->path(),
        ])->toString();
    }
    
}