<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\Auth\Contracts\Abstract2FAChallengeView;
use Snicco\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

class TwoFactorChallengeView extends Abstract2FaChallengeView
{
    
    private ViewEngine           $view_engine;
    private InternalUrlGenerator $url;
    
    public function __construct(ViewEngine $view_engine, InternalUrlGenerator $url)
    {
        $this->view_engine = $view_engine;
        $this->url = $url;
    }
    
    public function toResponsable()
    {
        return $this->view_engine->make('framework.auth.two-factor-challenge')->with([
            'post_to' => $this->url->toLogin(),
        ])->toString();
    }
    
}