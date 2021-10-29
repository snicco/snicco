<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Mailable;

class WelcomeMail extends Mailable
{
    
    public function build() :Mailable
    {
        return $this->view('mails.welcome_html.php')
                    ->from('c@web.de', 'Calvin INC')
                    ->reply_to('office@web.de', 'Front Office')
                    ->subject('welcome to our site')
                    ->attach(['file1', 'file2']);
    }
    
    public function unique() :bool
    {
        return false;
    }
    
}