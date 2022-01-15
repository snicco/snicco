<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\View\ViewEngine;
use Snicco\Component\Core\Utils\WP;
use Snicco\Auth\Contracts\AbstractLoginView;
use Snicco\Component\Core\Configuration\WritableConfig;
use Snicco\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

class PasswordLoginView extends AbstractLoginView
{
    
    private InternalUrlGenerator $url;
    private ViewEngine           $view_engine;
    private WritableConfig       $config;
    private bool                 $pw_resets;
    private bool                 $registration;
    private bool                 $allow_remember;
    
    public function __construct(ViewEngine $view, InternalUrlGenerator $url, WritableConfig $config)
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
                                 )->toString();
    }
    
}