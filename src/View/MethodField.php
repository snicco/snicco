<?php


    declare(strict_types = 1);


    namespace WPMvc\View;


    class MethodField
    {

        /**
         * @var string
         */
        private $app_key;

        public function __construct(string $app_key)
        {
            $this->app_key = $app_key;
        }

        public function html(string $method) : string
        {

            $method = strtoupper($method);

            if ( ! $this->allowedMethod($method) ) {
                throw new \LogicException("Unsupported HTTP method [$method] used.");
            }

            $signature = $this->sign($method);
            $value = $method . '|' . $signature;

            return "<input type='hidden' name='_method' value='{$value}'>";

        }

        public function string(string $method ) : string
        {
            $method = strtoupper($method);

            if ( ! $this->allowedMethod($method) ) {
                throw new \LogicException("Unsupported HTTP method [$method] used.");
            }

            $signature = $this->sign($method);
            $value = $method . '|' . $signature;

            return "_method=$value";
        }

        private function allowedMethod(string $method ) : bool
        {
            $valid = ['PUT', 'PATCH', 'DELETE'];

            $method = strtoupper($method);

            return in_array($method, $valid);
        }

        private function sign(string $method)
        {

            return hash_hmac('sha256', "method_override_$method", $this->app_key);
        }

        public function validate(string $signature)
        {
            [$method, $signature] = explode('|', $signature);

            if ( ! $this->allowedMethod($method) ) {
                return false;
            }

            $expected_signature = $this->sign($method);

            $valid = hash_equals($signature, $expected_signature);

            if ( ! $valid ) {
                return false;
            }

            return $method;

        }


    }