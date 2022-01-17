<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

final class CustomSenderMail extends Email
{
    
    public function configure()
    {
        $this->subject('foo')->sender('c@web.de', 'Calvin Alkan')->addFrom(
            'm@web.de',
            'Marlon Alkan'
        )->text('foo')->returnPath('return@company.de', 'My Company');
    }
    
}