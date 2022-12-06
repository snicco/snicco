<?php

declare(strict_types=1);

namespace Snicco\Bundle\Encryption;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Component\Kernel\Bundle;
use Snicco\Component\Kernel\Configuration\Config;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function copy;
use function dirname;
use function is_file;

final class EncryptionBundle implements Bundle
{
    /**
     * @var string
     */
    public const ALIAS = 'snicco/encryption-bundle';

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
        $this->copyConfiguration($kernel);

        $kernel->afterConfiguration(function (WritableConfig $config) {
            if (! $config->has($config_key = 'encryption.' . EncryptionOption::KEY_ASCII)) {
                throw new InvalidArgumentException(
                    $config_key . " is not set.\nGenerate a new config_key by running 'vendor/bin/generate-defuse-key'"
                );
            }

            try {
                $this->validateKey($config, $config_key);
            } catch (BadFormatException $e) {
                throw new InvalidArgumentException(
                    "Your encryption key is not valid.\nPlease generate a correct key by following the instructions in the encryption.php config file.\nMessage: {$e->getMessage()}",
                    $e->getCode(),
                    $e
                );
            }
        });
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()
            ->shared(DefuseEncryptor::class, fn (): DefuseEncryptor => new DefuseEncryptor(
                Key::loadFromAsciiSafeString(
                    $kernel->config()
                        ->getString('encryption.' . EncryptionOption::KEY_ASCII)
                )
            ));
    }

    public function bootstrap(Kernel $kernel): void
    {
    }

    public function alias(): string
    {
        return self::ALIAS;
    }

    /**
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    private function validateKey(Config $config, string $config_key): void
    {
        Key::loadFromAsciiSafeString($config->getString($config_key));
    }

    private function copyConfiguration(Kernel $kernel): void
    {
        if (! $kernel->env()->isDevelop()) {
            return;
        }

        $destination = $kernel->directories()
            ->configDir() . '/encryption.php';
        if (is_file($destination)) {
            return;
        }

        $copied = copy(dirname(__DIR__) . '/config/encryption.php', $destination);

        if (! $copied) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf(
                'Could not copy the default templating config to destination [%s]',
                $destination
            ));
            // @codeCoverageIgnoreEnd
        }
    }
}
