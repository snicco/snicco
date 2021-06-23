<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Arr;
    use WPEmerge\View\ViewFactory;

    class PasswordLoginResponse extends LoginViewResponse
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
                                          [
                                              'title' => 'Log-in | '.WP::siteName(),
                                              'view' => 'auth-login-password',
                                              'allow_remember' => $this->allowRememberMe(),
                                              'is_interim_login' => $this->request->boolean('interim-login'),
                                              'forgot_password' => $this->url->toRoute('auth.forgot.password'),
                                              'post_url' => $this->url->toRoute('auth.login'),
                                          ]
                                      );
        }



    }