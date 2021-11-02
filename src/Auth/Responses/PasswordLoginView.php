<?php

declare(strict_types=1);

namespace Snicco\Auth\Responses;

use Snicco\Support\WP;
use Snicco\View\ViewFactory;
use Snicco\Application\Config;
use Snicco\Routing\UrlGenerator;
use Snicco\Auth\Contracts\AbstractLoginView;

class PasswordLoginView extends AbstractLoginView
{
    
    private string       $view = 'framework.auth.layout';
    private UrlGenerator $url;
    private ViewFactory  $view_factory;
    private Config       $config;
    private bool         $pw_resets;
    private bool         $registration;
    
    public function __construct(ViewFactory $view, UrlGenerator $url, Config $config)
    {
        $this->view_factory = $view;
        $this->url = $url;
        $this->config = $config;
        $this->pw_resets = $this->config->get('auth.features.password-resets');
        $this->registration = $this->config->get('auth.features.registration');
    }
    
    public function toResponsable()
    {
        
        return $this->view_factory->make($this->view)->with(
            array_filter([
                'title' => 'Log-in | '.WP::siteName(),
                'view' => 'framework.auth.login-with-password',
                'allow_remember' => $this->config->get('auth.features.remember_me'),
                'allow_password_reset' => $this->pw_resets,
                'forgot_password_url' => $this->pw_resets
                    ? $this->url->toRoute('auth.forgot.password')
                    : null,
                'post_url' => $this->url->toRoute('auth.login'),
                'allow_registration' => $this->registration,
                'register_url' => $this->registration
                    ? $this->url->toRoute('auth.register')
                    : null,
            ], fn($value) => $value !== null)
        );
    }
    
}