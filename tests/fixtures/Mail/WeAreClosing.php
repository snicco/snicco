<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Mailable;

class WeAreClosing extends Mailable
{
    
    public function build() :Mailable
    {
        return $this
            ->text('we_are_closing.php')
            ->subject('We have to close soon.');
    }
    
    public function unique() :bool
    {
        return false;
    }
    
}