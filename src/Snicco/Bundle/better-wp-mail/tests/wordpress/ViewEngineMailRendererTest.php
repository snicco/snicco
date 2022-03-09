<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPMailDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;
use Snicco\Bundle\BetterWPMail\Option\MailOption;
use Snicco\Bundle\BetterWPMail\ViewEngineMailRenderer;
use Snicco\Bundle\Templating\Option\TemplatingOption;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPMail\Exception\CouldNotRenderMailContent;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\ViewEngine;

use function dirname;

final class ViewEngineMailRendererTest extends WPTestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_templated_emails_work(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $transport = $this->getTransport($kernel);
        $mailer = $this->getMailer($kernel);

        $email = (new Email())->withTo('c@web.de')->withHtmlTemplate('html-template');
        $email = $email->withContext([
            'mail_content' => 'FOO',
            'extra' => 'extra-bar',
        ]);

        $mailer->send($email);

        $transport->assertSent(Email::class, function (Email $email) {
            $this->assertStringContainsString('<h1>FOO</h1>', (string) $email->htmlBody());
            $this->assertStringContainsString('extra-bar', (string) $email->htmlBody());
            $this->assertStringContainsString('FOO', (string) $email->textBody());
            $this->assertStringContainsString('extra-bar', (string) $email->textBody());
            return true;
        });

        $transport->reset();

        // Mail context is reset.
        $email = $email->withContext('mail_content', 'BAR');
        $mailer->send($email);

        $transport->assertSent(Email::class, function (Email $email) {
            $this->assertSame('<h1>BAR</h1>', $email->htmlBody());
            $this->assertSame('BAR', $email->textBody());
            return true;
        });
    }

    /**
     * @test
     */
    public function test_nothing_breaks_if_template_is_not_supported(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $mailer = $this->getMailer($kernel);

        $email = (new Email())->withTo('c@web.de')->withHtmlTemplate('other-template');
        $email = $email->withContext('mail_content', 'FOO');

        $this->expectException(CouldNotRenderMailContent::class);
        $this->expectExceptionMessage(
            'None of the given renderers supports the current the template [other-template].'
        );

        $mailer->send($email);
    }

    /**
     * @test
     */
    public function view_rendering_exceptions_are_converted_to_the_correct_exception(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $mailer = $this->getMailer($kernel);

        $email = (new Email())->withTo('c@web.de')->withHtmlTemplate('html-template');
        // mail content var is not set.
        $email = $email->withContext('foo', 'bar');

        $this->expectException(CouldNotRenderMailContent::class);
        $this->expectExceptionMessage(
            'Error rendering view [html-template].'
        );

        $mailer->send($email);
    }

    /**
     * @test
     */
    public function test_supports(): void
    {
        $kernel = $this->getKernel();
        $kernel->boot();

        $renderer = new ViewEngineMailRenderer($kernel->container()->make(ViewEngine::class));

        $this->assertTrue($renderer->supports('html-template'));
        $this->assertTrue($renderer->supports('html-template'));
        $this->assertFalse($renderer->supports('bogus'));
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

    private function getKernel(): Kernel
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles', [
                Environment::ALL => [
                    BetterWPMailBundle::class,
                    TemplatingBundle::class,
                ],
            ]);
            $config->set('mail', [
                MailOption::RENDERER => [
                    ViewEngineMailRenderer::class,
                ],
            ]);
            $config->set('templating', [
                TemplatingOption::DIRECTORIES => [
                    $this->fixturesDir() . '/templates',
                ],
            ]);
        });
        return $kernel;
    }

    private function getTransport(Kernel $kernel): FakeTransport
    {
        /** @var FakeTransport $transport */
        $transport = $kernel->container()->get(Transport::class);
        return $transport;
    }

    private function getMailer(Kernel $kernel): Mailer
    {
        return $kernel->container()->make(Mailer::class);
    }
}
