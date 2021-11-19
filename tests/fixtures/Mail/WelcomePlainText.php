<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Email;

class WelcomePlainText extends Email
{
    
    public function configure() :Email
    {
        return $this->text('mails.welcome_plain')
                    ->from('c@web.de', 'Calvin INC')
                    ->reply_to('office@web.de', 'Front Office')
                    ->subject('welcome to our site')
                    ->attach(['file1', 'file2']);
    }
    
    public function unique() :bool
    {
        return true;
    }
    
    public function subjectLine($recipient) :string
    {
        return 'welcome to our site '.$recipient->name;
    }
    
}