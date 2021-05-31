<?php


    declare(strict_types = 1);


    namespace WPEmerge\View;

    use WPEmerge\Support\VariableBag;

    class GlobalContext
    {

        /** @var array */
        private $context = [];

        public function add(string $name, $context) {

            if ( is_array($context) ) {

                $context = new VariableBag($context);

            }

            $this->context[$name] = $context;
        }

        public function get() {
            return $this->context;
        }

    }