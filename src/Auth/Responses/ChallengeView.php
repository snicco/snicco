<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;

class ChallengeView extends Abstract2FaChallengeView
{
    
    private ViewFactory  $view_factory;
    private UrlGenerator $url;
    
    public function __construct(ViewFactory $view_factory, UrlGenerator $url)
    {
        $this->view_factory = $view_factory;
        $this->url = $url;
    }
    
    public function toResponsable()
    {
        return $this->view_factory->make('auth-layout')->with([
            'view' => 'auth-two-factor-challenge',
            'post_to' => $this->url->toLogin(),
        ]);
    }
    
}