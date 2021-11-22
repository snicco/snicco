<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Envelope;
use Snicco\Mail\Contracts\ImmutableEmail;

final class TestableEmail implements ImmutableEmail
{
    
    use InspectRecipients;
    
    /**
     * @var ImmutableEmail
     */
    private $mail;
    /**
     * @var Envelope
     */
    private $envelope;
    
    public function __construct(ImmutableEmail $mail, Envelope $envelope)
    {
        $this->mail = $mail;
        $this->envelope = $envelope;
        $this->to = $this->mail->getTo();
        $this->cc = $this->mail->getCc();
        $this->bcc = $this->mail->getBcc();
    }
    
    public function getEnvelope() :Envelope
    {
        return $this->envelope;
    }
    
    /**
     * @inheritdoc
     */
    public function getAttachments() :array
    {
        return $this->mail->getAttachments();
    }
    
    /**
     * @inheritdoc
     */
    public function getBcc() :array
    {
        return $this->mail->getBcc();
    }
    
    /**
     * @inheritdoc
     */
    public function getCc() :array
    {
        return $this->mail->getCc();
    }
    
    /**
     * @inheritdoc
     */
    public function getTo() :array
    {
        return $this->mail->getTo();
    }
    
    /**
     * @inheritdoc
     */
    public function getFrom() :array
    {
        return $this->mail->getFrom();
    }
    
    /**
     * @inheritdoc
     */
    public function getHtmlBody()
    {
        return $this->mail->getHtmlBody();
    }
    
    public function getHtmlCharset() :?string
    {
        return $this->mail->getHtmlCharset();
    }
    
    /**
     * @inheritdoc
     */
    public function getReplyTo() :array
    {
        return $this->mail->getReplyTo();
    }
    
    public function getPriority() :?int
    {
        return $this->mail->getPriority();
    }
    
    public function getReturnPath() :?Address
    {
        return $this->mail->getReturnPath();
    }
    
    public function getSender() :?Address
    {
        return $this->mail->getSender();
    }
    
    public function getSubject() :string
    {
        return $this->mail->getSubject();
    }
    
    /**
     * @inheritdoc
     */
    public function getTextBody()
    {
        return $this->mail->getTextBody();
    }
    
    public function getTextCharset() :?string
    {
        return $this->mail->getTextCharset();
    }
    
    /**
     * @inheritdoc
     */
    public function getCustomHeaders() :array
    {
        return $this->mail->getCustomHeaders();
    }
    
    /**
     * @inheritdoc
     */
    public function getCid(string $filename) :string
    {
        return $this->mail->getCid($filename);
    }
    
    public function getContext() :array
    {
        return $this->mail->getContext();
    }
    
    public function getHtmlTemplate() :?string
    {
        return $this->mail->getHtmlTemplate();
    }
    
    public function getTextTemplate() :?string
    {
        return $this->mail->getTextTemplate();
    }
    
}
