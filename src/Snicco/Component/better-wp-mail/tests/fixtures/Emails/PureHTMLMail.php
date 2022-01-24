<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class PureHTMLMail extends Email
{
    
    protected $subject = 'foo';
    
    public function configure() :void
    {
        $file = dirname(__DIR__, 2).'/fixtures/html-mail.html';
        $this->htmlTemplate($file);
    }
    
}