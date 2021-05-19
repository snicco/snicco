<?php


    declare(strict_types = 1);


    namespace Tests\stubs\Conditions;

    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Http\Request;

    class IsPost implements ConditionInterface
    {


        /**
         * @var bool
         */
        private $pass;

        public function __construct(bool $pass = true)
        {

            $this->pass = $pass;
        }

        public function isSatisfied(Request $request) : bool
        {

            return $this->pass;
        }

        public function getArguments(Request $request) : array
        {

            return [];
        }

    }