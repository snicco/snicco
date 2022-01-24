<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Testing;

use Snicco\Component\BetterWPMail\ValueObjects\Address;

trait InspectRecipients
{
    
    /**
     * @var Address[]
     */
    private array $to;
    
    /**
     * @var Address[]
     */
    private array $cc;
    
    /**
     * @var Address[]
     */
    private array $bcc;
    
    public function hasTo($recipient, bool $compare_email_only = true) :bool
    {
        $expected_recipient = Address::create($recipient);
        
        foreach ($this->to as $recipient) {
            if ($expected_recipient->toString() !== $recipient->toString()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_recipient->getAddress() === $recipient->getAddress()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasCC($recipient, $compare_email_only = true) :bool
    {
        $expected_cc = Address::create($recipient);
        
        foreach ($this->cc as $cc) {
            if ($expected_cc->toString() !== $cc->toString()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_cc->getAddress() === $cc->getAddress()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasBcc($recipient, $compare_email_only = true) :bool
    {
        $expected_bcc = Address::create($recipient);
        
        foreach ($this->bcc as $bcc) {
            if ($expected_bcc->toString() !== $bcc->toString()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_bcc->getAddress() === $bcc->getAddress()) {
                return true;
            }
        }
        
        return false;
    }
    
}