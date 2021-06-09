<?php


    declare(strict_types = 1);


    namespace WPEmerge\Validation\Exceptions;

    class SameAsException extends \Respect\Validation\Exceptions\ValidationException
    {

        protected $defaultTemplates = [
            self::MODE_DEFAULT => [
                self::STANDARD => '{{compare_to}} must be equal to {{name}}',
            ],
            self::MODE_NEGATIVE => [
                self::STANDARD => '{{compare_to}} must be different than {{name}}',
            ],
        ];

    }