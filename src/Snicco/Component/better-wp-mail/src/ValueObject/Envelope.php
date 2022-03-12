<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\ValueObject;

/*
 * Slight modified version of the symfony/mailer package envelope
 * https://github.com/symfony/mailer/blob/5.3/Envelope.php
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * License: MIT, https://github.com/symfony/mailer/blob/5.3/LICENSE
 */

use InvalidArgumentException;

use function count;

final class Envelope
{
    private Mailbox $sender;

    private MailboxList $recipients;

    public function __construct(Mailbox $sender, MailboxList $recipients)
    {
        // to ensure deliverability of bounce emails independent of UTF-8 capabilities of SMTP servers
        if (! preg_match('#^[^@\x80-\xFF]++@#', $sender->address())) {
            // @codeCoverageIgnoreStart
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid sender "%s": non-ASCII characters not supported in local-part of email.',
                    $sender->address()
                )
            );
            // @codeCoverageIgnoreEnd
        }

        if (0 === count($recipients)) {
            throw new InvalidArgumentException('An envelope must have at least one recipient.');
        }

        $this->sender = $sender;
        $this->recipients = $recipients;
    }

    public function sender(): Mailbox
    {
        return $this->sender;
    }

    public function recipients(): MailboxList
    {
        return $this->recipients;
    }
}
