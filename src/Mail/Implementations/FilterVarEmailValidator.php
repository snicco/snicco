<?php

declare(strict_types=1);

namespace Snicco\Mail\Implementations;

use Snicco\Mail\Contracts\EmailValidator;

final class FilterVarEmailValidator implements EmailValidator
{
    
    public function valid(string $email) :bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
}