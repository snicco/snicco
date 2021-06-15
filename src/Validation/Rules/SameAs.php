<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Rules;

    use Respect\Validation\Rules\AbstractRule;
    use WPEmerge\Support\Arr;

    class SameAs extends AbstractRule
    {

        /**
         * @var string
         */
        private $compare_to;

        /**
         * @var string
         */
        private $key = '';

        public function __construct(string $compare_to)
        {
            $this->compare_to = $compare_to;
        }

        public function validate($input) : bool
        {
            $compare = Arr::get($input, '__mapped_key');

            $this->key = $compare;

            if ( ! isset($input[$this->compare_to] ) || ! $compare ) {

                return false;
            }

            $actual_value = $input[$compare];
            $desired_value = $input[$this->compare_to];

            return $actual_value === $desired_value;

        }

    }