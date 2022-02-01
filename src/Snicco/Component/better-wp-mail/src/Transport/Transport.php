<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Transport;

use Snicco\Component\BetterWPMail\Exception\CantSendEmail;
use Snicco\Component\BetterWPMail\ValueObjects\Email;
use Snicco\Component\BetterWPMail\ValueObjects\Envelope;

/**
 * @api
 */
interface Transport
{

    /**
     * @throws CantSendEmail
     */
    public function send(Email $email, Envelope $envelope): void;

}