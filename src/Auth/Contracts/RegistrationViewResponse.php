<?php


    declare(strict_types = 1);


    namespace Snicco\Auth\Contracts;

    use Snicco\Contracts\ResponsableInterface;
    use Snicco\Http\Psr7\Request;

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