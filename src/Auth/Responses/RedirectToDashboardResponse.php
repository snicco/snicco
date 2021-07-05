<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Responses;

    use WPMvc\Auth\Contracts\LoginResponse;
    use WPMvc\Contracts\AbstractRedirector;
    use WPMvc\Http\Responses\RedirectResponse;
    use WPMvc\Session\StatefulRedirector;

    class RedirectToDashboardResponse extends LoginResponse
    {

        /**
         * @var StatefulRedirector
         */
        private $redirector;

        public function __construct(AbstractRedirector $redirector)
        {
            $this->redirector = $redirector;
        }

        public function toResponsable() : RedirectResponse
        {
            return $this->redirectToDashboard();
        }

        private function redirectToDashboard() : RedirectResponse
        {
            return $this->redirector->toRoute('dashboard');
        }

    }