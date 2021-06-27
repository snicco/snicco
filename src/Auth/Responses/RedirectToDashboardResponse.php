<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Auth\Contracts\LoginResponse;
    use WPEmerge\Contracts\AbstractRedirector;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Session\StatefulRedirector;

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
            return $this->redirector->intended($this->request);
        }

    }