<?php

declare(strict_types=1);

namespace Snicco\Validation\Exceptions;

class SameAsException extends \Respect\Validation\Exceptions\ValidationException
{
    
    protected $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{key}} must be equal to {{compare_to}}',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{key}} must be different than {{compare_to}}',
        ],
    ];
    
}