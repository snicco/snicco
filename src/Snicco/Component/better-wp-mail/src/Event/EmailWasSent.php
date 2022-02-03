<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Event;

use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;

/**
 * @api
 */
final class EmailWasSent
{

    private Email $email;
    private Envelope $envelope;

    public function __construct(Email $email, Envelope $envelope)
    {
        $this->email = $email;
        $this->envelope = $envelope;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function envelope(): Envelope
    {
        return $this->envelope;
    }

}