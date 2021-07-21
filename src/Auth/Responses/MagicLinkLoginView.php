<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Responses;

    use Snicco\Application\Config;
    use Snicco\Auth\Contracts\LoginViewResponse;
    use Snicco\Support\WP;
    use Snicco\Routing\UrlGenerator;
    use Snicco\View\ViewFactory;

    class MagicLinkLoginView extends LoginViewResponse
    {

        private $view = 'auth-login-via-email';

        /**
         * @var UrlGenerator
         */
        private $url;

        /**
         * @var ViewFactory
         */
        private $view_factory;
        /**
         * @var Config
         */
        private $config;

        public function __construct(ViewFactory $view, UrlGenerator $url, Config $config)
        {
            $this->view_factory = $view;
            $this->url = $url;
            $this->config = $config;
        }

        public function toResponsable()
        {

            return $this->view_factory->make('auth-layout')->with(array_filter([
                'title' => 'Log in | '. WP::siteName(),
                'view' => $this->view,
                'post_to' => $this->url->toRoute('auth.login.create-magic-link'),
                'register_url' => $this->config->get('auth.features.registration') ? $this->url->toRoute('auth.register') : null,
            ]));

        }

    }