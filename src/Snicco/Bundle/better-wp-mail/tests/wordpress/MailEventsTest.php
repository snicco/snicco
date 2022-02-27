<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPMailDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;
use Snicco\Bundle\BetterWPMail\Option\MailOption;
use Snicco\Bundle\Testing\BundleTestHelpers;
use Snicco\Component\BetterWPMail\Event\EmailWasSent;
use Snicco\Component\BetterWPMail\Event\SendingEmail;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\EventDispatcher\Testing\TestableEventDispatcher;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function add_action;
use function dirname;
use function get_class;

final class MailEventsTest extends WPTestCase
{

    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_mail_events_without_better_wp_hooks_and_exposed_events(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('mail', [
                MailOption::EXPOSE_MAIL_EVENTS => true
            ]);
        });

        $kernel->boot();

        /**
         * @var Mailer $mailer
         */
        $mailer = $kernel->container()->get(Mailer::class);

        /**
         * @var FakeTransport $transport
         */
        $transport = $kernel->container()->get(Transport::class);

        $called = false;
        $was_sent_called = false;

        add_action(TestEmail::class, function (SendingEmail $event) use (&$called) {
            $called = true;
            $this->assertSame(TestEmail::class, get_class($event->email));
        });

        add_action(EmailWasSent::class, function (EmailWasSent $event) use (&$was_sent_called) {
            $this->assertTrue($event->envelope()->recipients()->has('c@web.de'));
            $was_sent_called = true;
        });

        $email = (new TestEmail())->withTo('c@web.de')->withTextBody('foo');

        $mailer->send($email);

        $this->assertTrue($called, 'sending email event not dispatched');
        $this->assertTrue($was_sent_called, 'Mail sent event not dispatched');

        $transport->assertSentTo('c@web.de', TestEmail::class);
    }

    /**
     * @test
     */
    public function test_mail_events_without_better_wp_hooks_and_without_exposed_events(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->boot();

        /**
         * @var Mailer $mailer
         */
        $mailer = $kernel->container()->get(Mailer::class);

        /**
         * @var FakeTransport $transport
         */
        $transport = $kernel->container()->get(Transport::class);

        add_action(TestEmail::class, function () {
            throw new RuntimeException('This should not be called');
        });

        add_action(EmailWasSent::class, function () {
            throw new RuntimeException('This should not be called');
        });

        $email = (new TestEmail())->withTo('c@web.de')->withTextBody('foo');

        $mailer->send($email);

        $transport->assertSentTo('c@web.de', TestEmail::class);
    }

    /**
     * @test
     */
    public function test_mail_events_with_event_dispatcher_bound_and_exposed_events(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [
                    BetterWPHooksBundle::class,
                    BetterWPMailBundle::class,
                ]
            ]);
            $config->set('mail', [
                MailOption::EXPOSE_MAIL_EVENTS => true
            ]);
        });

        $kernel->boot();

        /**
         * @var Mailer $mailer
         */
        $mailer = $kernel->container()->get(Mailer::class);

        /**
         * @var TestableEventDispatcher $testable_dispatcher
         */
        $testable_dispatcher = $kernel->container()->get(TestableEventDispatcher::class);

        /**
         * @var FakeTransport $fake_transport
         */
        $fake_transport = $kernel->container()->get(Transport::class);

        $called = false;
        $was_sent_called = false;

        $testable_dispatcher->listen(TestEmail::class, function (SendingEmail $event) {
            $event->email = $event->email->addTo('m@web.de');
        });

        add_action(TestEmail::class, function (SendingEmail $event) use (&$called) {
            $called = true;
            $this->assertSame(TestEmail::class, get_class($event->email));
        });

        add_action(EmailWasSent::class, function (EmailWasSent $event) use (&$was_sent_called) {
            $this->assertTrue($event->envelope()->recipients()->has('c@web.de'));
            $was_sent_called = true;
        });

        $email = (new TestEmail())->withTo('c@web.de')->withTextBody('foo');

        $mailer->send($email);

        $testable_dispatcher->assertDispatched(TestEmail::class);
        $testable_dispatcher->assertDispatched(EmailWasSent::class);

        $fake_transport->assertSentTo('c@web.de', TestEmail::class);
        $fake_transport->assertSentTo('m@web.de', TestEmail::class);

        $this->assertTrue($called, 'sending email event not dispatched');
        $this->assertTrue($was_sent_called, 'Mail sent event not dispatched');
    }

    /**
     * @test
     */
    public function test_mail_events_with_event_dispatcher_bound_and_without_exposed_events(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [
                    BetterWPHooksBundle::class,
                    BetterWPMailBundle::class,
                ]
            ]);
        });

        $kernel->boot();

        /**
         * @var Mailer $mailer
         */
        $mailer = $kernel->container()->get(Mailer::class);

        /**
         * @var TestableEventDispatcher $testable_dispatcher
         */
        $testable_dispatcher = $kernel->container()->get(TestableEventDispatcher::class);

        /**
         * @var FakeTransport $fake_transport
         */
        $fake_transport = $kernel->container()->get(Transport::class);

        $testable_dispatcher->listen(TestEmail::class, function (SendingEmail $event) {
            $event->email = $event->email->addTo('m@web.de');
        });

        add_action(TestEmail::class, function () {
            throw new RuntimeException('This should not be called.');
        });

        add_action(EmailWasSent::class, function () {
            throw new RuntimeException('This should not be called.');
        });

        $email = (new TestEmail())->withTo('c@web.de')->withTextBody('foo');

        $mailer->send($email);

        $testable_dispatcher->assertDispatched(TestEmail::class);
        $testable_dispatcher->assertDispatched(EmailWasSent::class);

        $fake_transport->assertSentTo('c@web.de', TestEmail::class);
        $fake_transport->assertSentTo('m@web.de', TestEmail::class);
    }


    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

}

class TestEmail extends Email
{

}