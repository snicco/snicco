<?php

declare(strict_types=1);

namespace Tests\fixtures\Mail;

use Snicco\Mail\Mailable;

class WelcomePlainText extends Mailable
{
    
    public function build() :Mailable
    {
        return $this->text('welcome_plain.php')
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