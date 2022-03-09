<?php

declare(strict_types=1);

namespace Snicco\Bundle\Encryption\Tests;

use Defuse\Crypto\Key;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

final class EncryptionBundleTest extends TestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => Key::createNewRandomKey()->saveToAsciiSafeString(),
            ]);
        });
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle(EncryptionBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_encryptor_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => Key::createNewRandomKey()->saveToAsciiSafeString(),
            ]);
        });
        $kernel->boot();

        $this->assertCanBeResolved(DefuseEncryptor::class, $kernel);
    }

    /**
     * @test
     */
    public function test_encrypt_decrypt(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => Key::createNewRandomKey()->saveToAsciiSafeString(),
            ]);
        });
        $kernel->boot();

        /** @var DefuseEncryptor $encryptor */
        $encryptor = $kernel->container()->get(DefuseEncryptor::class);

        $cipher_text = $encryptor->encrypt('foo');
        $this->assertNotSame('foo', $cipher_text);

        $plaintext = $encryptor->decrypt($cipher_text);
        $this->assertSame('foo', $plaintext);
    }

    /**
     * @test
     */
    public function test_exception_for_missing_defuse_key(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [

            ]);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('encryption.key_ascii is not set.');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_for_bad_defuse_key(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => Key::createNewRandomKey()->saveToAsciiSafeString() . 'bad',
            ]);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Your encryption key is not valid');
        $kernel->boot();
    }

    /**
     * @test
     */
    public function the_default_configuration_is_copied_to_the_config_directory_if_it_does_not_exist(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertFalse(is_file($this->directories->configDir() . '/encryption.php'));

        try {
            $kernel->boot();
        } catch (InvalidArgumentException $e) {
            //
        }

        $this->assertTrue(is_file($this->directories->configDir() . '/encryption.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/encryption.php';

        $this->assertSame(
            require dirname(__DIR__, 1) . '/config/encryption.php',
            $config
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_not_copied_if_the_file_already_exists(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $key = Key::createNewRandomKey()->saveToAsciiSafeString();

        file_put_contents(
            $this->directories->configDir() . '/encryption.php',
            '<?php return ' . var_export([
                EncryptionOption::KEY_ASCII => $key,
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/encryption.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame(
            [
                EncryptionOption::KEY_ASCII => $key,
            ],
            require $this->directories->configDir() . '/encryption.php'
        );
    }

    /**
     * @test
     */
    public function the_default_configuration_is_only_copied_in_dev_environment(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::prod(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => Key::createNewRandomKey()->saveToAsciiSafeString(),
            ]);
        });

        $this->assertFalse(is_file($this->directories->configDir() . '/encryption.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/encryption.php'));
    }

    protected function fixturesDir(): string
    {
        return __DIR__ . '/fixtures';
    }
}
