<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class ReplyToMultipleMail extends Email
{
    
    public function configure()
    {
        $this->addReplyTo('c@web.de', 'Calvin Alkan')
             ->addReplyTo('m@web.de', 'Marlon Alkan')
             ->html('foo')->subject('bar');
    }
    
}