<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress\Transport;

use Codeception\TestCase\WPTestCase;
use PHPMailer\PHPMailer\Exception;
use Snicco\Component\BetterWPMail\Exception\CantSendEmailWithWPMail;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObject\Email;

use function add_action;

/**
 * @internal
 */
final class WPMailTransportTest extends WPTestCase
{
    /**
     * @test
     */
    public function php_mailer_exception_are_caught_and_converted_to_our_custom_exception(): void
    {
        $php_mailer_exception = new Exception('bad email debug data.');

        add_action('wp_mail_content_type', function () use ($php_mailer_exception): void {
            throw $php_mailer_exception;
        });

        $mailer = new Mailer(new WPMailTransport());

        try {
            $mailer->send((new Email())->addTo('calvin@web.de')->withTextBody('txt'));
            $this->fail('No exception thrown');
        } catch (CantSendEmailWithWPMail $e) {
            $this->assertSame($php_mailer_exception, $e->getPrevious());
            $this->assertSame('bad email debug data.', $e->getDebugData());
        }
    }
}
