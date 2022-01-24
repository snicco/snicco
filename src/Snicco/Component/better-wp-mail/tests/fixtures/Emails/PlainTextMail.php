<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class PlainTextMail extends Email
{
    
    /**
     * @var string|null
     */
    private $_message;
    
    public function __construct(string $_message = null)
    {
        $this->_message = $_message;
    }
    
    public function configure() :void
    {
        $this->subject('Hello');
        if ($this->_message) {
            $this->text($this->_message);
        }
        else {
            $file = dirname(__DIR__, 2).'/fixtures/plain-text-mail.txt';
            $this->textTemplate($file);
        }
    }
    
}