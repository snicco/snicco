<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

interface EmailValidator
{
    
    public function valid(string $email) :bool;
    
}