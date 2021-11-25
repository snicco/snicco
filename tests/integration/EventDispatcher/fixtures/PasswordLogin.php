<?php

declare(strict_types=1);

namespace Tests\integration\EventDispatcher\fixtures;

use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;

class PasswordLogin extends AbstractLogin
{
    
    use ClassAsName;
    use ClassAsPayload;
    
    public function message()
    {
        return 'password login';
    }
    
}