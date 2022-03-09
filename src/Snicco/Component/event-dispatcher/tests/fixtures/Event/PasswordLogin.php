<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

class PasswordLogin extends AbstractLogin
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @return string
     *
     * @psalm-return 'password login'
     */
    public function message()
    {
        return 'password login';
    }
}
