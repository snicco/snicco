<?php

declare(strict_types=1);

namespace Snicco\Mail\Event;

use Snicco\Mail\Email;
use Snicco\Mail\ValueObjects\Envelope;
use Snicco\Mail\Contracts\ImmutableEmail;

final class EmailWasSent
{
    
    /**
     * @var Email
     */
    private $email;
    
    /**
     * @var Envelope
     */
    private $envelope;
    
    public function __construct(Email $email, Envelope $envelope)
    {
        $this->email = $email;
        $this->envelope = $envelope;
    }
    
    public function getEmail() :ImmutableEmail
    {
        return clone $this->email;
    }
    
    public function getEnvelope() :Envelope
    {
        return clone $this->envelope;
    }
    
}