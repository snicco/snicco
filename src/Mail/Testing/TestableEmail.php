<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Snicco\Mail\ValueObjects\CC;
use Snicco\Mail\ValueObjects\CCs;
use Snicco\Mail\ValueObjects\BCC;
use Snicco\Mail\ValueObjects\BCCs;
use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\ReplyTo;
use Snicco\Mail\ValueObjects\Recipient;
use Snicco\Mail\Contracts\ImmutableEmail;
use Snicco\Mail\ValueObjects\Recipients;

final class TestableEmail implements ImmutableEmail
{
    
    private ImmutableEmail $mail;
    private Recipients     $recipients;
    private CCs            $ccs;
    private BCCs           $bccs;
    
    public function __construct(ImmutableEmail $mail, Recipients $recipients, CCs $ccs, BCCs $bccs)
    {
        $this->mail = $mail;
        $this->recipients = $recipients;
        $this->ccs = $ccs;
        $this->bccs = $bccs;
    }
    
    public function hasTo($recipient, bool $compare_email_only = true) :bool
    {
        $expected_recipient = Address::normalize($recipient, Recipient::class);
        
        foreach ($this->recipients->getValid()->toArray() as $recipient) {
            if ($expected_recipient->formatted() !== $recipient->formatted()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_recipient->getEmail() === $recipient->getEmail()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasCC($recipient, $compare_email_only = true) :bool
    {
        $expected_cc = Address::normalize($recipient, CC::class);
        
        foreach ($this->ccs->getValid()->toArray() as $cc) {
            if ($expected_cc->formatted() !== $cc->formatted()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_cc->getEmail() === $cc->getEmail()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function hasBcc($recipient, $compare_email_only = true)
    {
        $expected_bcc = Address::normalize($recipient, BCC::class);
        
        foreach ($this->bccs->getValid()->toArray() as $bcc) {
            if ($expected_bcc->formatted() !== $bcc->formatted()
                && ! $compare_email_only) {
                continue;
            }
            
            if ($expected_bcc->getEmail() === $bcc->getEmail()) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getFrom() :From
    {
        return $this->mail->getFrom();
    }
    
    public function getReplyTo() :ReplyTo
    {
        return $this->mail->getReplyTo();
    }
    
    public function getContentType() :string
    {
        return $this->mail->getContentType();
    }
    
    public function getSubject() :string
    {
        return $this->mail->getSubject();
    }
    
    public function getMessage() :string
    {
        return $this->mail->getMessage();
    }
    
    public function getAttachments() :array
    {
        return $this->mail->getAttachments();
    }
    
}
