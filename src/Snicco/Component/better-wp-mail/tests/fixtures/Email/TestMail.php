<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Email;

use Snicco\Component\BetterWPMail\ValueObjects\Email;

class TestMail extends Email
{
    
    protected string $subject = 'foo';
    protected string $text    = 'bar';
    
}