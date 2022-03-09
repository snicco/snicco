<?php

declare(strict_types=1);


namespace Snicco\Bundle\Session\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Snicco\Bridge\SessionWP\WPDBSessionDriver;
use Snicco\Bridge\SessionWP\WPObjectCacheDriver;
use Snicco\Bundle\BetterWPCache\BetterWPCacheBundle;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\BetterWPHooks\BetterWPHooksBundle;
use Snicco\Bundle\Encryption\DefuseEncryptor;
use Snicco\Bundle\Encryption\EncryptionBundle;
use Snicco\Bundle\Encryption\Option\EncryptionOption;
use Snicco\Bundle\Session\DefuseSessionEncryptor;
use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Bundle\Session\Option\SessionOption;
use Snicco\Bundle\Session\SessionBundle;
use Snicco\Bundle\Testing\Bundle\BundleTest;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPDB\BetterWPDB;
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
use stdClass;

use function dirname;
use function file_put_contents;
use function is_file;
use function var_export;

final class SessionBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    private BetterWPDB $better_wpdb;
    private string $dbname;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle_test = new BundleTest($this->fixturesDir());
        $this->directories = $this->bundle_test->setUpDirectories();
        $this->better_wpdb = BetterWPDB::fromWpdb();
        $this->better_wpdb->unprepared('drop table if exists `my_plugin_sessions`');
        $this->dbname = (string)($_ENV['DB_NAME'] ?? '');
    }

    protected function tearDown(): void
    {
        $this->better_wpdb->unprepared('drop table if exists my_plugin_sessions');
        $this->bundle_test->tearDownDirectories();
        parent::tearDown();
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

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
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle(SessionBundle::ALIAS));
    }

    /**
     * @test
     */
    public function test_services_can_be_resolved(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->instance(LoggerInterface::class, new NullLogger());
        });
        $kernel->boot();

        $this->assertCanBeResolved(SessionManager::class, $kernel);
        $this->assertInstanceOf(FactorySessionManager::class, $kernel->container()->get(SessionManager::class));
        $this->assertInstanceOf(InMemoryDriver::class, $kernel->container()->make(SessionDriver::class));
        $this->assertCanBeResolved(StatefulRequest::class, $kernel);
    }

    /**
     * @test
     */
    public function test_stateful_request_middleware_is_singleton(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->instance(LoggerInterface::class, new NullLogger());
        });
        $kernel->boot();

        $this->assertSame(
            $kernel->container()->make(StatefulRequest::class),
            $kernel->container()->make(StatefulRequest::class)
        );
    }

    /**
     * @test
     */
    public function test_exception_if_session_config_is_invalid(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::testing(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::COOKIE_NAME => 'foo',
                SessionOption::CONFIG => [
                    'same_site' => 'bogus',
                ],
            ]);
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('same_site');

        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_with_db_session_driver_creates_table(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $this->assertSame(
            0,
            $this->better_wpdb->selectValue(
                'select exists(select * from information_schema.TABLES where TABLE_NAME = ? AND TABLE_SCHEMA = ?)',
                ['my_plugin_sessions', $this->dbname]
            )
        );

        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);
        $this->assertInstanceOf(WPDBSessionDriver::class, $kernel->container()->make(SessionDriver::class));

        $this->assertSame(
            1,
            $this->better_wpdb->selectValue(
                'select exists(select * from information_schema.TABLES where TABLE_NAME = ? AND TABLE_SCHEMA = ?)',
                ['my_plugin_sessions', $this->dbname]
            )
        );
    }

    /**
     * @test
     */
    public function test_with_object_cache_driver(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::DRIVER => WPObjectCacheDriver::class,
            ]);
            $config->extend('bundles.all', BetterWPCacheBundle::class);
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);
        $this->assertInstanceOf(WPObjectCacheDriver::class, $kernel->container()->make(SessionDriver::class));
    }

    /**
     * @test
     */
    public function test_with_encryption(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::ENCRYPT_DATA => true,
            ]);
            $config->extend('bundles.all', EncryptionBundle::class);
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => DefuseEncryptor::randomAsciiKey(),
            ]);
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);
        $this->assertInstanceOf(EncryptedDriver::class, $kernel->container()->make(SessionDriver::class));
    }

    /**
     * @test
     */
    public function test_session_encryptor(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::ENCRYPT_DATA => true,
            ]);
            $config->extend('bundles.all', EncryptionBundle::class);
            $config->set('encryption', [
                EncryptionOption::KEY_ASCII => DefuseEncryptor::randomAsciiKey(),
            ]);
        });
        $kernel->boot();

        $session_encryptor = new DefuseSessionEncryptor($kernel->container()->make(DefuseEncryptor::class));

        $ciphertext = $session_encryptor->encrypt('foo');
        $this->assertNotSame('foo', $ciphertext);
        $this->assertSame('foo', $session_encryptor->decrypt($ciphertext));
    }

    /**
     * @test
     */
    public function test_different_serializers(): void
    {
        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::SERIALIZER => JsonSerializer::class,
            ]);
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);


        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::SERIALIZER => PHPSerializer::class,
            ]);
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::SERIALIZER => TestSerializer::class,
            ]);
        });
        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->shared(TestSerializer::class, fn () => new TestSerializer());
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_serializer_has_wrong_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('class-name that implements [Snicco\Component\Session\Serializer\Serializer]');

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::SERIALIZER => stdClass::class,
            ]);
        });
        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_driver_has_wrong_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('class-name that implements');

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::DRIVER => stdClass::class,
            ]);
        });
        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_prefix_is_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty string');

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );

        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::PREFIX => '',
            ]);
        });
        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_encryption_is_turned_on_but_bundle_is_not_used(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(EncryptionBundle::class);

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::ENCRYPT_DATA => true,
            ]);
        });
        $kernel->boot();
        $this->assertCanBeResolved(SessionManager::class, $kernel);
    }

    /**
     * @test
     */
    public function test_exception_if_better_wp_hooks_bundle_not_used(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(BetterWPHooksBundle::class);

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles.all', [
                SessionBundle::class,
                BetterWPDBBundle::class,
            ]);
        });
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

        $this->assertFalse(is_file($this->directories->configDir() . '/session.php'));

        $kernel->boot();

        $this->assertTrue(is_file($this->directories->configDir() . '/session.php'));

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $config = require $this->directories->configDir() . '/session.php';

        $this->assertSame(
            require dirname(__DIR__, 2) . '/config/session.php',
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

        file_put_contents(
            $this->directories->configDir() . '/session.php',
            '<?php return ' . var_export([
                'editor' => 'sublime',
            ], true) . ';'
        );

        $this->assertTrue(is_file($this->directories->configDir() . '/session.php'));

        $kernel->boot();

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $this->assertSame([
            'editor' => 'sublime',
        ], require $this->directories->configDir() . '/session.php');
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

        $this->assertFalse(is_file($this->directories->configDir() . '/session.php'));

        $kernel->boot();

        $this->assertFalse(is_file($this->directories->configDir() . '/session.php'));
    }

    /**
     * @test
     */
    public function test_exception_if_db_driver_is_used_but_better_wpdb_bundle_is_not(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(BetterWPDBBundle::class);

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('bundles.all', [
                SessionBundle::class,
            ]);
        });
        $kernel->boot();
    }

    /**
     * @test
     */
    public function test_exception_if_object_cache_driver_is_used_but_better_wpdb_bundle_is_not(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(BetterWPCacheBundle::class);

        $kernel = new Kernel(
            $this->newContainer(),
            Environment::dev(),
            $this->directories
        );
        $kernel->afterConfigurationLoaded(function (WritableConfig $config) {
            $config->set('session', [
                SessionOption::DRIVER => WPObjectCacheDriver::class,
            ]);
            $config->set('bundles.all', [
                SessionBundle::class,
            ]);
        });
        $kernel->boot();
    }
}

class TestSerializer implements Serializer
{
    public function serialize(array $session_data): string
    {
        return '';
    }

    public function deserialize(string $data): array
    {
        return [];
    }
}
