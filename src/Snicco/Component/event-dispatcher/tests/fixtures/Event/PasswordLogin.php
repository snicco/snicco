<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Tests\fixtures\Event;

use Snicco\Component\EventDispatcher\ClassAsName;
use Snicco\Component\EventDispatcher\ClassAsPayload;

final class PasswordLogin extends AbstractLogin
{
    use ClassAsName;
    use ClassAsPayload;

    /**
     * @psalm-return 'password login'
     */
    public function message(): string
    {
        return 'password login';
    }
}
