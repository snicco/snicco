<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Rules;

    use WPEmerge\Validation\Rule;

    class SameAs extends Rule
    {

        /**
         * @var string
         */
        private $compare_to;

        public function __construct(string $compare_to)
        {

            $this->compare_to = $compare_to;
        }

        public function validate($compare_against) : bool
        {

            return $this->payload[$compare_against] === $this->payload[$this->compare_to];

        }

    }