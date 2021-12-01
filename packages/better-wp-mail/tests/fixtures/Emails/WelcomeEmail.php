<?php

declare(strict_types=1);

namespace Tests\BetterWPMail\fixtures\Emails;

use Snicco\Mail\Email;

class WelcomeEmail extends Email
{
    
    protected $priority = 5;
    /**
     * @var string|null
     */
    private $file;
    
    public function __construct(string $first_name = '', string $file = null)
    {
        $this->file = $file;
        $this->first_name = $first_name;
    }
    
    public function configure()
    {
        $this->subject("Hi {$this->first_name}")
             ->html('hey whats up.');
        
        if ($this->file) {
            $this->attach($this->file);
        }
    }
    
}