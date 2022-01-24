<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class CustomHeaderMail extends Email
{
    
    protected $subject = 'foo';
    
    public function configure() :void
    {
        $this->addFrom('calvin@web.de', 'Calvin Alkan');
        $this->addReplyTo('marlon@web.de', 'Marlon Alkan');
        $this->text('bar');
    }
    
}