<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\View\ViewFactory;

    class MagicLinkLoginView extends LoginViewResponse
    {

        private $view = 'auth-login-email';

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

            return $this->view_factory->make('auth-parent')->with([
                'title' => 'Log in | '. WP::siteName(),
                'view' => $this->view,
                'post_to' => $this->url->toRoute('auth.login.create-magic-link'),
            ]);

        }

    }