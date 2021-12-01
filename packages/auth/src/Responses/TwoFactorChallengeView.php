<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;

class TwoFactorChallengeView extends Abstract2FaChallengeView
{
    
    private ViewEngine   $view_engine;
    private UrlGenerator $url;
    
    public function __construct(ViewEngine $view_factory, UrlGenerator $url)
    {
        $this->view_engine = $view_factory;
        $this->url = $url;
    }
    
    public function toResponsable()
    {
        return $this->view_engine->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $this->url->toLogin(),
        ]);
    }
    
}