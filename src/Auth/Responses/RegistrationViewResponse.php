<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WPEmerge\Contracts\ResponsableInterface;
    use WPEmerge\Http\Psr7\Request;

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