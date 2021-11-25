<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Email;
use Snicco\Mail\ValueObjects\Recipient;

class WelcomeMail extends Email
{
    
    public function configure(Recipient $recipient) :void
    {
        $this->view('mails.welcome_html.php')
             ->from('c@web.de', 'Calvin INC')
             ->reply_to('office@web.de', 'Front Office')
             ->subject("welcome to our site [{$recipient->getName()}]")
             ->attach(__FILE__);
    }
    
}