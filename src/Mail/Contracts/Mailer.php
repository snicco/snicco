<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\ValueObjects\Envelope;

/**
 * @api
 */
interface Mailer
{
    
    /**
     * @param  ImmutableEmail  $email
     * @param  Envelope  $envelope
     *
     * @throws TransportException
     */
    public function send(ImmutableEmail $email, Envelope $envelope) :void;
    
}