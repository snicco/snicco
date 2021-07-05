<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Responses;

    use BetterWP\Auth\Contracts\LoginResponse;
    use BetterWP\Contracts\AbstractRedirector;
    use BetterWP\Http\Responses\RedirectResponse;
    use BetterWP\Session\StatefulRedirector;

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