<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Testing\WPMail;

use function add_filter;
use function count;
use function dirname;
use function get_option;
use function wp_mail;

/**
 * @internal
 */
final class FakeTransportTest extends WPTestCase
{
    /**
     * @test
     */
    public function test_exception_if_wp_site_name_is_malformed(): void
    {
        $fake_transport = new FakeTransport();
        $fake_transport->interceptWordPressEmails();

        add_filter('pre_option_home', fn (): string => 'bogus');

        if ('bogus' !== get_option('home')) {
            throw new RuntimeException('Could not update home url in test setup.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cant parse site url [bogus].');

        wp_mail('calvin@web.de', 'subject', 'message');
    }

    /**
     * @test
     */
    public function test_www_is_stripped_from_site_name(): void
    {
        $fake_transport = new FakeTransport();
        $fake_transport->interceptWordPressEmails();

        add_filter('pre_option_home', fn (): string => 'https://www.foobar.com');

        if ('https://www.foobar.com' !== get_option('home')) {
            throw new RuntimeException('Could not update home url in test setup.');
        }

        wp_mail('calvin@web.de', 'subject', 'message');

        $fake_transport->assertSent(WPMail::class, fn (WPMail $WPMail): bool => $WPMail->from()
            ->has('wordpress@foobar.com'));
    }

    /**
     * @test
     */
    public function attachments_from_wp_mail_are_recorded(): void
    {
        $fake_transport = new FakeTransport();
        $fake_transport->interceptWordPressEmails();

        wp_mail('calvin@web.de', 'subject', 'message', '', [dirname(__DIR__) . '/fixtures/php-elephant.jpg']);

        $fake_transport->assertSent(WPMail::class, fn (WPMail $mail): bool => 1 === count($mail->attachments()));
    }
}
