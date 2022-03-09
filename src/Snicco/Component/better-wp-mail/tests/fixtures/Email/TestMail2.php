<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Email;

use Snicco\Component\BetterWPMail\ValueObject\Email;

class TestMail2 extends Email
{
    public function __construct()
    {
        $this->subject = 'subject';
        $this->text = 'bar';
    }
}
