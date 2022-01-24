<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class ReplyToMultipleMail extends Email
{
    
    public function configure()
    {
        $this->addReplyTo('c@web.de', 'Calvin Alkan')
             ->addReplyTo('m@web.de', 'Marlon Alkan')
             ->html('foo')->subject('bar');
    }
    
}