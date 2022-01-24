<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures\Emails;

use Snicco\Component\BetterWPMail\Email;

class IncorrectMail extends Email
{
    
    /**
     * @var string|null
     */
    private $m;
    /**
     * @var string|null
     */
    private $s;
    
    public function __construct(string $s = null, string $m = null)
    {
        $this->m = $m;
        $this->s = $s;
    }
    
    public function configure() :void
    {
        if ($this->m) {
            $this->text($this->m);
        }
        if ($this->s) {
            $this->subject($this->s);
        }
    }
    
}