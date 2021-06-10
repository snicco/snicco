<?php


    declare(strict_types = 1);


    namespace Tests\unit\View;

    use WPEmerge\Http\Psr7\Request;

    class MethodField
    {

        /**
         * @var string
         */
        private $secret;

        public function __construct(string $app_key)
        {

            $this->secret = hash_hmac('sha256', 'method_override', $app_key);

        }

        public function html(string $method) : string
        {

            $valid = ['PUT', 'PATCH', 'DELETE'];

            $method = strtoupper($method);

            if( ! in_array($method, $valid) ) {
                throw new \LogicException("Unsupported HTTP method [$method] used.");
            }

            return "<input type='hidden' name='{$this->key()}' value='{$method}'>";

        }

        public function methodOverride (Request $request) {

            return $request->post($this->key(), $request->getMethod());

        }

        public function key () : string
        {

            return '_method_overwrite_' . $this->secret;
        }

    }