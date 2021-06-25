<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Auth\Contracts\LoginViewResponse;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class PasswordLoginView extends LoginViewResponse
    {

        private $view = 'auth-parent';

        /**
         * @var UrlGenerator
         */
        private $url;

        /**
         * @var ViewFactory
         */
        private $view_factory;

        public function __construct(ViewFactory $view, UrlGenerator $url)
        {

            $this->view_factory = $view;
            $this->url = $url;
        }

        public function toResponsable()
        {

            return $this->view_factory->make($this->view)
                                      ->with(
                                          array_filter([
                                              'title' => 'Log-in | '.WP::siteName(),
                                              'view' => 'auth-login-password',
                                              'allow_remember' => $this->allowRememberMe(),
                                              'is_interim_login' => $this->request->boolean('interim-login'),
                                              'allow_password_reset' => AUTH_ENABLE_PASSWORD_RESETS,
                                              'forgot_password_url' => AUTH_ENABLE_PASSWORD_RESETS ? $this->url->toRoute('auth.forgot.password') : null,
                                              'post_url' => $this->url->toRoute('auth.login'),
                                              'register_url' => AUTH_ENABLE_REGISTRATION ? $this->url->toRoute('auth.register') : null,
                                          ], function ($value) {
                                              return $value !== null;
                                          })
                                      );
        }


    }