<?php

declare(strict_types=1);

namespace Snicco\Auth;

use Snicco\Component\StrArr\Str;

class RecoveryCode
{
    
    public static function generate() :string
    {
        return Str::random(10).'-'.Str::random(10);
    }
    
}