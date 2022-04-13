<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPMail;

use LogicException;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Snicco\Bundle\BetterWPMail\Option\MailOption;
use Snicco\Component\BetterWPMail\Event\MailEvents;
use Snicco\Component\BetterWPMail\Event\MailEventsUsingWPHooks;
use Snicco\Component\BetterWPMail\Event\NullEvents;
use Snicco\Component\BetterWPMail\Mailer;
use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Renderer\FilesystemRenderer;
use Snicco\Component\BetterWPMail\Renderer\MailRenderer;
use Snicco\Component\BetterWPMail\Testing\FakeTransport;
use Snicco\Component\BetterWPMail\Transport\Transport;
use Snicco\Component\BetterWPMail\Transport\WPMailTransport;
use Snicco\Component\BetterWPMail\ValueObject\MailDefaults;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Templating\TemplateEngine;

use function array_map;
use function class_exists;
use function copy;
use function dirname;
use function is_file;
use function sprintf;

final class BetterWPMailBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'sniccowp/better-wp-mail-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $config->mergeDefaultsFromFile(dirname(__DIR__) . '/config/mail.php');
        $this->copyConfiguration($kernel);
    }

    public function register(Kernel $kernel): void
    {
        $this->bindTransport($kernel);
        $this->bindMailer($kernel);
        $this->bindViewEngineRenderer($kernel);
        $this->bindMailEvents($kernel);
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function bindMailer(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(Mailer::class, function () use ($kernel): Mailer {
                $config = $kernel->config();
                $from_name = $config->getString('mail.' . MailOption::FROM_NAME);
                $from_email = $config->getString('mail.' . MailOption::FROM_EMAIL);
                $reply_to_name = $config->getString('mail.' . MailOption::REPLY_TO_NAME);
                $reply_to_email = $config->getString('mail.' . MailOption::REPLY_TO_EMAIL);

                $default_config = new MailDefaults($from_name, $from_email, $reply_to_email, $reply_to_name);

                /** @var class-string<MailRenderer>[] $renderer_names */
                $renderer_names = $config->getListOfStrings('mail.' . MailOption::RENDERER);
                $renderers = array_map(function ($class) use ($kernel): MailRenderer {
                    if (FilesystemRenderer::class === $class) {
                        return new FilesystemRenderer();
                    }

                    return $kernel->container()
                        ->make($class);
                }, $renderer_names);

                $renderer = new AggregateRenderer(...$renderers);

                return new Mailer(
                    $kernel->container()
                        ->make(Transport::class),
                    $renderer,
                    $kernel->container()
                        ->make(MailEvents::class),
                    $default_config
                );
            });
    }

    private function bindTransport(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(Transport::class, function () use ($kernel): Transport {
                if ($kernel->env()->isTesting() && class_exists(FakeTransport::class)) {
                    return new FakeTransport();
                }

                /** @var class-string<Transport> $transport_class */
                $transport_class = $kernel->config()
                    ->getString('mail.' . MailOption::TRANSPORT);

                if (WPMailTransport::class === $transport_class) {
                    return new WPMailTransport();
                }

                return $kernel->container()
                    ->make($transport_class);
            });
    }

    private function bindViewEngineRenderer(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(TemplateEngineMailRenderer::class, function () use ($kernel): TemplateEngineMailRenderer {
                try {
                    $engine = $kernel->container()
                        ->make(TemplateEngine::class);
                } catch (NotFoundExceptionInterface $e) {
                    throw new LogicException(
                        sprintf(
                            "The ViewEngine is not bound in the container. Make sure that you are using the templating-bundle or that you bind an instance of [%s].\n%s",
                            TemplateEngine::class,
                            $e->getMessage()
                        ),
                        0,
                        $e
                    );
                }

                return new TemplateEngineMailRenderer($engine);
            });
    }

    private function bindMailEvents(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(MailEvents::class, function () use ($kernel): MailEvents {
                $expose = $kernel->config()
                    ->getBoolean('mail.' . MailOption::EXPOSE_MAIL_EVENTS);

                if ($kernel->container()->has(EventDispatcher::class)) {
                    return new MailEventsUsingBetterWPHooks(
                        $kernel->container()
                            ->make(EventDispatcher::class),
                        $expose
                    );
                }

                return $expose ? new MailEventsUsingWPHooks() : new NullEvents();
            });
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/mail.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/mail.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(
                sprintf('Could not copy the default templating config to destination [%s]', $destination)
            );
            // @codeCoverageIgnoreEnd
        }
    }
}
