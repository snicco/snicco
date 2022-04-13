<?php

declare(strict_types=1);

namespace Snicco\Bundle\BetterWPDB\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Snicco\Bundle\Testing\Bundle\BundleTestHelpers;
use Snicco\Component\BetterWPDB\BetterWPDB;
use Snicco\Component\BetterWPDB\QueryInfo;
use Snicco\Component\BetterWPDB\QueryLogger;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Environment;

use function dirname;

/**
 * @internal
 */
final class BetterWPDBBundleTest extends WPTestCase
{
    use BundleTestHelpers;

    /**
     * @test
     */
    public function test_alias(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();

        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));
    }

    /**
     * @test
     */
    public function test_runs_in_all_environments(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::dev(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::dev(false), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::staging(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));

        $kernel = new Kernel($this->newContainer(), Environment::prod(), $this->directories);
        $kernel->boot();
        $this->assertTrue($kernel->usesBundle('snicco/better-wpdb-bundle'));
    }

    /**
     * @test
     */
    public function test_better_wpdb_can_be_resolved(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->boot();
        $this->assertCanBeResolved(BetterWPDB::class, $kernel);
    }

    /**
     * @test
     *
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    public function a_custom_query_logger_is_used_if_bound(): void
    {
        $kernel = new Kernel($this->newContainer(), Environment::testing(), $this->directories);
        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->instance(QueryLogger::class, new TestQueryLogger());
        });

        $kernel->boot();

        $this->assertCanBeResolved(BetterWPDB::class, $kernel);
        $this->assertCanBeResolved(QueryLogger::class, $kernel);

        /** @var TestQueryLogger $logger */
        $logger = $kernel->container()
            ->make(QueryLogger::class);

        $this->assertCount(0, $logger->logs);

        /** @var BetterWPDB $better_wpdb */
        $better_wpdb = $kernel->container()
            ->make(BetterWPDB::class);
        $better_wpdb->select('select * from wp_users', []);

        $this->assertTrue(isset($logger->logs[0]));
        $this->assertSame('select * from wp_users', $logger->logs[0]->sql);
    }

    protected function fixturesDir(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }
}

final class TestQueryLogger implements QueryLogger
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
