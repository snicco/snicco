<?php

declare(strict_types=1);

namespace Snicco\Mail\Testing;

use Snicco\Mail\Email;
use Snicco\Mail\ValueObjects\From;
use Snicco\Mail\ValueObjects\ReplyTo;
use Snicco\Mail\ValueObjects\Recipient;

final class WordPressTestMail extends Email
{
    
    private ?From    $from;
    private ?ReplyTo $reply_to;
    private array    $attachments;
    
    public function __construct(string $subject, string $message, ?From $from = null, ?ReplyTo $reply_to = null, array $attachments = [])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->from = $from;
        $this->reply_to = $reply_to;
        $this->attachments = $attachments;
    }
    
    public function configure(Recipient $recipient) :void
    {
        // do nothing.
    }
    
}