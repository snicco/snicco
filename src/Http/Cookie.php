<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    class Cookie
    {

        /**
         * Default cookie properties
         *
         * @var array
         */
        private $defaults = [
            'value' => '',
            'domain' => null,
            'hostonly' => true,
            'path' => '/',
            'expires' => null,
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        private $properties;

        /**
         * @var string
         */
        private $name;

        public function __construct(string $name, string $value )
        {

            $this->name = $name;

            $value = ['value' => $value];

            $this->properties = array_merge($this->defaults, $value);

        }

        public function properties() : array
        {
            return $this->properties;

        }

        public function name() :string
        {
            return $this->name;
        }


    }