<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPMailDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use LogicException;
use Snicco\Bundle\BetterWPMail\BetterWPMailBundle;
use Snicco\Bundle\BetterWPMail\Option\MailOption;
use Snicco\Bundle\BetterWPMail\TemplateEngineMailRenderer;
use Snicco\Bundle\Templating\TemplatingBundle;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObject\Email;
use Snicco\Component\BetterWPMail\ValueObject\Envelope;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

/**
 * @internal
 */
final class BetterWPMailBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('snicco/better-wp-mail-bundle'));
    }

    /**
     * @test
     */
    public function test_mailer_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertCanBeResolved(Mailer::class, $kernel);
    }

    /**
     * @test
     */
    public function test_transport_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertInstanceOf(FakeTransport::class, $kernel->container()->make(Transport::class));

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);
        $kernel->boot();
        $this->assertInstanceOf(WPMailTransport::class, $kernel->container()->make(Transport::class));
    }

    /**
     * @test
     */
    public function test_custom_transport_is_resolved_from_container(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $transport = new CustomTransport();

        $kernel->afterRegister(function (Kernel $kernel) use ($transport): void {
            $kernel->container()
                ->instance(CustomTransport::class, $transport);
        });

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('mail', [
                MailOption::TRANSPORT => CustomTransport::class,
            ]);
        });

        $kernel->boot();

        $this->assertInstanceOf(CustomTransport::class, $resolved = $kernel->container()->make(Transport::class));
        $this->assertSame($transport, $resolved);

        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();
        // still fake transport.
        $this->assertInstanceOf(FakeTransport::class, $kernel->container()->make(Transport::class));
    }

    /**
     * @test
     */
    public function test_with_view_engine_renderer_throws_exception_if_not_bound(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('mail', [
                MailOption::RENDERER => [TemplateEngineMailRenderer::class],
            ]);
        });

        $kernel->boot();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The ViewEngine is not bound in the container. Make sure that you are using the templating-bundle or that you bind an instance of'
        );

        $kernel->container()
            ->make(Mailer::class);
    }

    /**
     * @test
     */
    public function test_view_engine_renderer_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);

        $kernel->afterConfigurationLoaded(function (WritableConfig $config): void {
            $config->set('kernel.bundles', [
                Environment::ALL => [BetterWPMailBundle::class, TemplatingBundle::class],
            ]);
            $config->set('mail', [
                MailOption::RENDERER => [TemplateEngineMailRenderer::class],
            ]);
        });

        $kernel->boot();

        $this->assertCanBeResolved(Mailer::class, $kernel);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/mail.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/mail.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/mail.php';

        $this->assertSame(require dirname(__DIR__, 2) . '/config/mail.php', $config);
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);

        file_put_contents(
            $this->directories->configDir() . '/mail.php',
            '<?php return ' . var_export([
                MailOption::REPLY_TO_NAME => 'calvin',
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/mail.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [
                MailOption::REPLY_TO_NAME => 'calvin',
            ],
            require $this->directories->configDir() . '/mail.php'
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);

        $this->assertFalse(is_file($this->directories->configDir() . '/mail.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/mail.php'));
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}

final class CustomTransport implements Transport
{
    public function send(Email $email, Envelope $envelope): void
    {
    }
}
