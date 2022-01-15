<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class NamedViewEmail extends Email
{
    
    protected $subject = 'foo';
    
    public function configure() :void
    {
        $this->htmlTemplate('mail.foobar-mail');
    }
    
}