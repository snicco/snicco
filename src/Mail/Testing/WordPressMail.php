<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Snicco\Mail\ValueObjects\Address;
use Snicco\Mail\ValueObjects\Attachment;

final class WordPressMail
{
    
    use InspectRecipients;
    
    /**
     * @var string
     */
    private $subject;
    
    /**
     * @var string
     */
    private $message;
    
    /**
     * @var Address[]
     */
    private $from;
    
    /**
     * @var Address[]
     */
    private $reply_to;
    
    /**
     * @var Attachment[]
     */
    private $attachments;
    
    public function __construct(
        string $subject,
        string $message,
        array $to,
        array $cc,
        array $bcc,
        array $from = [],
        array $reply_to = [],
        array $attachments = []
    ) {
        $this->subject = $subject;
        $this->message = $message;
        $this->from = $from;
        $this->reply_to = $reply_to;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        foreach ($attachments as $attachment) {
            $this->attachments[] = Attachment::fromPath($attachment);
        }
    }
    
    public function getAttachments() :array
    {
        return $this->attachments;
    }
    
    public function getBcc() :array
    {
        return $this->bcc;
    }
    
    public function getCc() :array
    {
        return $this->cc;
    }
    
    public function getTo() :array
    {
        return $this->to;
    }
    
    public function getFrom() :array
    {
        return $this->from;
    }
    
    public function getHtmlBody()
    {
        return $this->message;
    }
    
    public function getReplyTo() :array
    {
        return $this->reply_to;
    }
    
    public function getSubject() :string
    {
        return $this->subject;
    }
    
    public function getTextBody()
    {
        return $this->message;
    }
    
}