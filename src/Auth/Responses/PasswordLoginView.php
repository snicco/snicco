<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Support\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class PasswordLoginView extends LoginViewResponse
    {

        private $view = 'auth-layout';

        /**
         * @var UrlGenerator
         */
        private $url;

        /**
         * @var ViewFactory
         */
        private $view_factory;
        /**
         * @var ApplicationConfig
         */
        private $config;

        /**
         * @var bool
         */
        private $pw_resets;

        /**
         * @var bool
         */
        private $registraion;

        public function __construct(ViewFactory $view, UrlGenerator $url, ApplicationConfig $config)
        {

            $this->view_factory = $view;
            $this->url = $url;
            $this->config = $config;
            $this->pw_resets = $this->config->get('auth.features.password-resets');
            $this->registraion = $this->config->get('auth.features.registration');
        }

        public function toResponsable()
        {

            return $this->view_factory->make($this->view)
                                      ->with(
                                          array_filter([
                                              'title' => 'Log-in | '.WP::siteName(),
                                              'view' => 'auth-login-via-password',
                                              'allow_remember' => $this->allowRememberMe(),
                                              'is_interim_login' => $this->request->boolean('interim-login'),
                                              'allow_password_reset' => $this->pw_resets,
                                              'forgot_password_url' => $this->pw_resets ? $this->url->toRoute('auth.forgot.password') : null,
                                              'post_url' => $this->url->toRoute('auth.login'),
                                              'allow_registration' =>  $this->registraion,
                                              'register_url' => $this->registraion ? $this->url->toRoute('auth.register') : null,
                                          ], function ($value) {
                                              return $value !== null;
                                          })
                                      );
        }


    }