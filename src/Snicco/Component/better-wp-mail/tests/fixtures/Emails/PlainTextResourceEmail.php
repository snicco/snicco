<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class PlainTextResourceEmail extends Email
{
    
    public function configure()
    {
        $this->text(fopen(dirname(__DIR__, 2).'/fixtures/plain-text-mail.txt', 'r'))->subject(
            'foo'
        );
    }
    
}