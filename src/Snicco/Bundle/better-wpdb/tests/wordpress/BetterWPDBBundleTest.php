<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\BetterWPDB\BetterWPDBBundle;
use Snicco\Bundle\Testing\BootsKernel;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\QueryInfo;
use Snicco\Component\BetterWPDB\QueryLogger;
use Snicco\Component\Kernel\Bootstrapper;
use Snicco\Component\Kernel\Configuration\WritableConfig;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;

final class BetterWPDBBundleTest extends WPTestCase
{

    use BootsKernel;

    private string $base_dir;
    private Directories $directories;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = dirname(__DIR__) . '/fixtures/tmp';
        $this->directories = $this->setUpDirectories($this->base_dir);
    }

    protected function tearDown(): void
    {
        $this->tearDownDirectories($this->base_dir);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);

        $this->assertSame(true, $kernel->usesBundle('sniccowp/better-wpdb-bundle'));
    }

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::testing());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wpdb-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::prod());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wpdb-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::dev());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wpdb-bundle'));

        $kernel = $this->bootWithFixedConfig([], $this->directories, Environment::staging());
        $this->assertTrue($kernel->usesBundle('sniccowp/better-wpdb-bundle'));
    }

    /**
     * @test
     */
    public function test_better_wpdb_can_be_resolved(): void
    {
        $kernel = $this->bootWithFixedConfig([], $this->directories);
        $this->assertCanBeResolved(BetterWPDB::class, $kernel);
    }

    /**
     * @test
     *
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    public function a_custom_query_logger_is_used_if_bound(): void
    {
        $kernel = $this->bootWithFixedConfig([
            'app' => [
                'bootstrappers' => [
                    CustomQueryLoggerBootstrapper::class,
                ]
            ]
        ], $this->directories);
        $this->assertCanBeResolved(BetterWPDB::class, $kernel);
        $this->assertCanBeResolved(QueryLogger::class, $kernel);

        /** @var TestQueryLogger $logger */
        $logger = $kernel->container()->make(QueryLogger::class);

        $this->assertCount(0, $logger->logs);

        /** @var BetterWPDB $better_wpdb */
        $better_wpdb = $kernel->container()->make(BetterWPDB::class);
        $better_wpdb->select('select * from wp_users', []);

        $this->assertTrue(isset($logger->logs[0]));
        $this->assertSame('select * from wp_users', $logger->logs[0]->sql);
    }

    protected function bundles(): array
    {
        return [
            Environment::ALL => [
                BetterWPDBBundle::class
            ]
        ];
    }

}

class TestQueryLogger implements QueryLogger
{

    /**
     * @var QueryInfo[]
     */
    public array $logs = [];

    public function log(QueryInfo $info): void
    {
        $this->logs[] = $info;
    }
}

class CustomQueryLoggerBootstrapper implements Bootstrapper
{

    public function shouldRun(Environment $env): bool
    {
        return true;
    }

    public function configure(WritableConfig $config, Kernel $kernel): void
    {
    }

    public function register(Kernel $kernel): void
    {
        $kernel->container()->singleton(QueryLogger::class, fn() => new TestQueryLogger());
    }

    public function bootstrap(Kernel $kernel): void
    {
    }
}