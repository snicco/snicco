<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Contracts;

    use WPMvc\Contracts\ResponsableInterface;
    use WPMvc\Http\Psr7\Request;

    abstract class RegistrationViewResponse implements ResponsableInterface
    {

        /**
         * @var Request
         */
        protected $request;

        public function setRequest(Request $request ) : RegistrationViewResponse
        {
            $this->request = $request;
            return $this;
        }
    }