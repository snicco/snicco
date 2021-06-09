<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation;

    use Respect\Validation\Rules\AbstractRule;

    abstract class Rule extends AbstractRule
    {

        protected $payload;

        public function setInput($payload) {

            $this->payload = $payload;

        }

    }