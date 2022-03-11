<?php

declare(strict_types=1);

namespace Snicco\Bundle\Session;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Snicco\Bridge\SessionPsr16\Psr16SessionDriver;
use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Bridge\SessionWP\WPObjectCacheDriver;
use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\Session\Event\WPLogin;
use Snicco\Bundle\Session\Event\WPLogout;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Bundle\Session\Option\SessionOption;
use Snicco\Component\BetterWPCache\CacheFactory;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPHooks\EventMapping\EventMapper;
use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\ReadOnlyConfig;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\Session\Driver\EncryptedDriver;
use Snicco\Component\Session\Driver\InMemoryDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Serializer\JsonSerializer;
use Snicco\Component\Session\Serializer\PHPSerializer;
use Snicco\Component\Session\Serializer\Serializer;
use Snicco\Component\Session\SessionManager\FactorySessionManager;
use Snicco\Component\Session\SessionManager\SessionManager;
use Snicco\Component\Session\ValueObject\SessionConfig;

use function array_replace;
use function class_exists;
use function class_implements;
use function copy;
use function dirname;
use function in_array;
use function is_file;
use function sprintf;

final class SessionBundle implements Bundle
{
    public const ALIAS = 'sniccowp/session-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $defaults = require dirname(__DIR__) . '/config/session.php';
        $config->set('session', array_replace($defaults, $config->getArray('session', [])));

        $this->validateConfig($kernel, $config);
        $this->copyConfig($kernel);
    }

    public function register(Kernel $kernel): void
    {
        if (! $kernel->usesBundle(BetterWPHooksBundle::ALIAS)) {
            throw new InvalidArgumentException(
                sprintf(
                    'You need to add [%s] to your bundles.php config if you are using the SessionBundle',
                    BetterWPHooksBundle::class,
                )
            );
        }

        $config = $kernel->config();
        $this->bindSessionConfig($kernel);
        $this->bindSessionDriver($kernel);
        $this->bindSessionManager($kernel);
        $this->bindWPDBSessionDriver($kernel, $config);
        $this->bindObjectCacheDriver($kernel, $config);
        $this->bindMiddleware($kernel);
    }

    public function bootstrap(Kernel $kernel): void
    {
        $event_mapper = $kernel->container()
            ->make(EventMapper::class);
        $event_mapper->map('wp_logout', WPLogout::class);
        $event_mapper->map('wp_login', WPLogin::class);
        $event_dispatcher = $kernel->container()
            ->make(EventDispatcher::class);
        $event_dispatcher->listen(WPLogout::class, [StatefulRequest::class, 'wpLogoutEvent']);
        $event_dispatcher->listen(WPLogin::class, [StatefulRequest::class, 'wpLoginEvent']);
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    private function bindSessionDriver(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(SessionDriver::class, function () use ($kernel): SessionDriver {
                if ($kernel->env()->isTesting()) {
                    return new InMemoryDriver();
                }

                /** @var class-string<SessionDriver> $driver_class */
                $driver_class = $kernel->config()
                    ->getString('session.' . SessionOption::DRIVER);

                $driver = $kernel->container()
                    ->make($driver_class);

                if ($kernel->config()->getBoolean('session.' . SessionOption::ENCRYPT_DATA)) {
                    $driver = new EncryptedDriver(
                        $driver,
                        new DefuseSessionEncryptor($kernel->container()->make(DefuseEncryptor::class))
                    );
                }

                return $driver;
            });
    }

    private function bindSessionManager(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(SessionManager::class, fn () => new FactorySessionManager(
                $kernel->container()
                    ->make(SessionConfig::class),
                $kernel->container()
                    ->make(SessionDriver::class),
                $this->resolveSerializer($kernel),
            ));
    }

    private function validateConfig(Kernel $kernel, WritableConfig $config): void
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        SessionConfig::mergeDefaults(
            $config->getString('session.' . SessionOption::COOKIE_NAME),
            $config->getArray('session.' . SessionOption::CONFIG)
        );

        $serializer = $config->getString('session.' . SessionOption::SERIALIZER);

        if (! class_exists($serializer) || ! in_array(Serializer::class, (array) class_implements($serializer), true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'session.' . SessionOption::SERIALIZER . " has to be a class-name that implements [%s].\nGot [%s].",
                    Serializer::class,
                    $serializer
                )
            );
        }

        $driver = $config->getString('session.' . SessionOption::DRIVER);
        if (! class_exists($driver) || ! in_array(SessionDriver::class, (array) class_implements($driver), true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'session.' . SessionOption::DRIVER . " has to be a class-name that implements [%s].\nGot [%s].",
                    SessionDriver::class,
                    $driver
                )
            );
        }

        if ('' === $config->getString('session.' . SessionOption::PREFIX)) {
            throw new InvalidArgumentException('session.' . SessionOption::PREFIX . ' must be a non-empty string.');
        }

        if ($config->getBoolean('session.' . SessionOption::ENCRYPT_DATA)
            && ! $kernel->usesBundle(EncryptionBundle::ALIAS)) {
            throw new InvalidArgumentException(
                sprintf(
                    'You need to add [%s] to your bundles.php config if [%s] is set to true.',
                    EncryptionBundle::class,
                    'session.' . SessionOption::ENCRYPT_DATA
                )
            );
        }

        if (WPDBSessionDriver::class === $driver && ! $kernel->usesBundle(BetterWPDBBundle::ALIAS)) {
            throw new InvalidArgumentException(
                sprintf(
                    'You need to add [%s] to your bundles.php config if you are using the database session driver',
                    BetterWPDBBundle::class,
                )
            );
        }
        if (WPObjectCacheDriver::class === $driver && ! $kernel->usesBundle(BetterWPCacheBundle::ALIAS)) {
            throw new InvalidArgumentException(
                sprintf(
                    'You need to add [%s] to your bundles.php config if you are using the object-cache session driver',
                    BetterWPCacheBundle::class,
                )
            );
        }
    }

    private function bindWPDBSessionDriver(Kernel $kernel, ReadOnlyConfig $config): void
    {
        $kernel->container()
            ->shared(WPDBSessionDriver::class, function () use ($kernel, $config): WPDBSessionDriver {
                /** @var non-empty-string $table */
                $table = $config->getString('session.' . SessionOption::PREFIX);

                $driver = new WPDBSessionDriver($table, $kernel->container()->make(BetterWPDB::class));

                $driver->createTable();

                return $driver;
            });
    }

    private function bindObjectCacheDriver(Kernel $kernel, ReadOnlyConfig $config): void
    {
        $kernel->container()
            ->shared(WPObjectCacheDriver::class, function () use ($kernel, $config): WPObjectCacheDriver {
                /** @var non-empty-string $group */
                $group = $config->getString('session.' . SessionOption::PREFIX);

                return new WPObjectCacheDriver(
                    new Psr16SessionDriver(
                        CacheFactory::psr16($group),
                        $kernel->container()
                            ->make(SessionConfig::class)->idleTimeoutInSec()
                    )
                );
            });
    }

    private function bindSessionConfig(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(SessionConfig::class, function () use ($kernel): SessionConfig {
                $cookie_name = $kernel->config()
                    ->getString('session.' . SessionOption::COOKIE_NAME);
                $config = $kernel->config()
                    ->getArray('session.' . SessionOption::CONFIG);
                /** @psalm-suppress MixedArgumentTypeCoercion $config */
                return SessionConfig::mergeDefaults($cookie_name, $config);
            });
    }

    private function resolveSerializer(Kernel $kernel): Serializer
    {
        /** @var class-string<Serializer> $serializer */
        $serializer = $kernel->config()
            ->getString('session.' . SessionOption::SERIALIZER);

        if (JsonSerializer::class === $serializer) {
            return new JsonSerializer();
        }
        if (PHPSerializer::class === $serializer) {
            return new PHPSerializer();
        }

        return $kernel->container()
            ->make($serializer);
    }

    private function copyConfig(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }
        $destination = $kernel->directories()
            ->configDir() . '/session.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/session.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Could not copy the default session config to destination [{$destination}]");
            // @codeCoverageIgnoreEnd
        }
    }

    private function bindMiddleware(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(StatefulRequest::class, fn () => new StatefulRequest(
                $kernel->container()
                    ->make(SessionManager::class),
                $kernel->container()
                    ->make(LoggerInterface::class),
                $kernel->container()
                    ->make(SessionConfig::class)->cookiePath()
            ));
    }
}
