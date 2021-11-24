<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Routing\UrlGenerator;
use Snicco\View\Contracts\ViewFactoryInterface;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;

class TwoFactorChallengeView extends Abstract2FaChallengeView
{
    
    private ViewFactoryInterface $view_factory;
    private UrlGenerator         $url;
    
    public function __construct(ViewFactoryInterface $view_factory, UrlGenerator $url)
    {
        $this->view_factory = $view_factory;
        $this->url = $url;
    }
    
    public function toResponsable()
    {
        return $this->view_factory->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $this->url->toLogin(),
        ]);
    }
    
}