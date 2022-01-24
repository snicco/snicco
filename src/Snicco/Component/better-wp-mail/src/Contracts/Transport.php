<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Contracts;

use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;

/**
 * @api
 */
interface Transport
{
    
    /**
     * @throws TransportException
     */
    public function send(Email $email, Envelope $envelope) :void;
    
}