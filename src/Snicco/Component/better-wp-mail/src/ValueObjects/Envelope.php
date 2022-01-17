<?php

declare(strict_types=1);

namespace Snicco\Mail\ValueObjects;

/*
 * Slight modified version of the symfony/mailer package envelope
 * https://github.com/symfony/mailer/blob/5.3/Envelope.php
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * License: MIT, https://github.com/symfony/mailer/blob/5.3/LICENSE
 */

use InvalidArgumentException;

/**
 * @api
 */
final class Envelope
{
    
    /**
     * @var Address
     */
    private $sender;
    
    /**
     * @var Address[]
     */
    private $recipients = [];
    
    public function __construct(Address $sender, Address ...$recipients)
    {
        // to ensure deliverability of bounce emails independent of UTF-8 capabilities of SMTP servers
        if ( ! preg_match('/^[^@\x80-\xFF]++@/', $sender->getAddress())) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid sender "%s": non-ASCII characters not supported in local-part of email.',
                    $sender->getAddress()
                )
            );
        }
        
        if ( ! count($recipients)) {
            throw new InvalidArgumentException('An envelope must have at least one recipient.');
        }
        
        $this->sender = $sender;
        $this->recipients = $recipients;
    }
    
    public function getSender() :Address
    {
        return $this->sender;
    }
    
    /**
     * @return Address[]
     */
    public function getRecipients() :array
    {
        return $this->recipients;
    }
    
}