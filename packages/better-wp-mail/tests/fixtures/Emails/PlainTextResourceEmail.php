<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class PlainTextResourceEmail extends Email
{
    
    public function configure()
    {
        $this->text(fopen(dirname(__DIR__, 2).'/fixtures/plain-text-mail.txt', 'r'))->subject(
            'foo'
        );
    }
    
}