<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Support\WP;
use Snicco\View\ViewEngine;
use Snicco\Application\Config;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\AbstractLoginView;

class PasswordLoginView extends AbstractLoginView
{
    
    private UrlGenerator $url;
    private ViewEngine   $view_engine;
    private Config       $config;
    private bool         $pw_resets;
    private bool         $registration;
    private bool         $allow_remember;
    
    public function __construct(ViewEngine $view, UrlGenerator $url, Config $config)
    {
        $this->view_engine = $view;
        $this->url = $url;
        $this->config = $config;
        $this->pw_resets = $this->config->get('auth.features.password-resets');
        $this->registration = $this->config->get('auth.features.registration');
        $this->allow_remember = $this->config->get('auth.features.remember_me');
    }
    
    public function toResponsable()
    {
        return $this->view_engine->make('framework.auth.login-with-password')
                                 ->with(
                                     array_filter([
                                         'title' => 'Log-in | '.WP::siteName(),
                                         'allow_remember' => $this->allow_remember,
                                         'allow_password_reset' => $this->pw_resets,
                                         'forgot_password_url' => $this->pw_resets
                                             ? $this->url->toRoute('auth.forgot.password')
                                             : null,
                                         'post_url' => $this->url->toRoute('auth.login'),
                                         'allow_registration' => $this->registration,
                                         'register_url' => $this->registration
                                             ? $this->url->toRoute('auth.register')
                                             : null,
                                     ], fn($value) => $value !== null),
                                 );
    }
    
}