<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Core\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Core\Application\Config;
use Snicco\Auth\Contracts\AbstractLoginView;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;

class MagicLinkLoginView extends AbstractLoginView
{
    
    private InternalUrlGenerator $url;
    private ViewEngine           $view_engine;
    private Config               $config;
    
    public function __construct(ViewEngine $view, InternalUrlGenerator $url, Config $config)
    {
        $this->view_engine = $view;
        $this->url = $url;
        $this->config = $config;
    }
    
    public function toResponsable()
    {
        return $this->view_engine->make('framework.auth.login-with-email')->with(
            array_filter([
                'title' => 'Log in | '.WP::siteName(),
                'post_to' => $this->url->toRoute('auth.login.create-magic-link'),
                'register_url' => $this->config->get('auth.features.registration')
                    ? $this->url->toRoute('auth.register') : null,
            ])
        )->toString();
    }
    
}