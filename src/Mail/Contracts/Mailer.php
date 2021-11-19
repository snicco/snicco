<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\ValueObjects\CCs;
use Snicco\Mail\ValueObjects\BCCs;
use Snicco\Mail\ValueObjects\Recipients;

/**
 * @api
 */
interface Mailer
{
    
    /**
     * @param  ImmutableEmail  $mail
     * @param  Recipients  $recipients
     * @param  CCs  $ccs
     * @param  BCCs  $bcc
     *
     * @throws TransportException
     */
    public function send(ImmutableEmail $mail, Recipients $recipients, CCs $ccs, BCCs $bcc) :void;
    
}