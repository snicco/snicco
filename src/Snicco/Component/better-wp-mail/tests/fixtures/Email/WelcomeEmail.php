<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Email;

use Snicco\Component\BetterWPMail\ValueObjects\Email;

class WelcomeEmail extends Email
{
    
    public function __construct()
    {
        $this->subject = 'Foo';
        $this->text = 'Bar';
    }
    
}