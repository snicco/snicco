<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

final class HtmlMail extends Email
{
    
    public function configure()
    {
        $this->html('<h1>Hello World</h1>')
             ->subject('Hi');
    }
    
}