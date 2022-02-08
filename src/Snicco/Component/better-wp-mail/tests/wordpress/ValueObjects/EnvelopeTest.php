<?php

declare(strict_types=1);


namespace Snicco\Component\BetterWPMail\Tests\wordpress\ValueObjects;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;
use Snicco\Component\BetterWPMail\ValueObject\Mailbox;
use Snicco\Component\BetterWPMail\ValueObject\MailboxList;

final class EnvelopeTest extends WPTestCase
{
    /**
     * @test
     */
    public function test_exception_for_empty_recipients(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An envelope must have at least one recipient.');

        new Envelope(Mailbox::create('calvin@web.de'), new MailboxList([]));
    }


}