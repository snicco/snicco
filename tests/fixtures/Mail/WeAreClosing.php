<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Email;

class WeAreClosing extends Email
{
    
    public function configure() :Email
    {
        return $this
            ->text('mails.we_are_closing.php')
            ->subject('We have to close soon.');
    }
    
    public function unique() :bool
    {
        return false;
    }
    
}