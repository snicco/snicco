<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class NamedViewEmail extends Email
{
    
    protected $subject = 'foo';
    
    public function configure() :void
    {
        $this->htmlTemplate('mail.foobar-mail');
    }
    
}