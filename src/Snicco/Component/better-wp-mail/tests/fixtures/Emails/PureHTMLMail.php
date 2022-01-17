<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class PureHTMLMail extends Email
{
    
    protected $subject = 'foo';
    
    public function configure() :void
    {
        $file = dirname(__DIR__, 2).'/fixtures/html-mail.html';
        $this->htmlTemplate($file);
    }
    
}