<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class PHPMail extends Email
{
    
    public  $foo;
    private $bar;
    private $baz = 'BAZ';
    
    public function __construct($foo, $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function configure() :void
    {
        $this->subject('Hello Calvin');
        $this->htmlTemplate(dirname(__DIR__, 2).'/fixtures/php-mail.php')
             ->context('baz', $this->baz);
    }
    
}