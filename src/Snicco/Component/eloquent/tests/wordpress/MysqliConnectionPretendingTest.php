<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\LazyCollection;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WithTestTables;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WPDBTestHelpers;
use Snicco\Component\Eloquent\WPEloquentStandalone;

final class MysqliConnectionPretendingTest extends WPTestCase
{
    use WPDBTestHelpers;
    use WithTestTables;

    /**
     * @test
     */
    public function test_nothing_gets_executed_for_inserts(): void
    {
        $connection = $this->getMysqliConnection();

        $count = $connection->table('cities')->where('country_id', 1)->count();
        $this->assertSame(2, $count);

        $sql = $connection->pretend(function (MysqliConnection $connection) {
            $result = $connection->table('cities')->insert(
                ['name' => 'düsseldorf', 'country_id' => 1, 'population' => 1]
            );
            $this->assertTrue($result);
        });

        // No records have been inserted.
        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());
        $this->assertSame(
            'insert into `wp_cities` (`name`, `country_id`, `population`) values (?, ?, ?)',
            $sql[0]['query']
        );
        $this->assertSame(['düsseldorf', 1, 1], $sql[0]['bindings']);
        $this->assertIsFloat($sql[0]['time']);
    }

    private function getMysqliConnection(): MysqliConnection
    {
        /** @var MysqliConnection $connection */
        $connection = DB::connection();
        return $connection;
    }

    /**
     * @test
     */
    public function test_nothing_gets_run_for_updates(): void
    {
        $connection = $this->getMysqliConnection();

        $count = $connection->table('cities')->where('country_id', 1)->count();
        $this->assertSame(2, $count);

        $sql = $connection->pretend(function (MysqliConnection $connection) {
            $result = $connection->table('cities')->where('country_id', 1)->update(
                ['country_id' => 3]
            );
            $this->assertSame(0, $result);
        });

        // No records have been inserted.
        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());
        $this->assertSame(
            'update `wp_cities` set `country_id` = ? where `country_id` = ?',
            $sql[0]['query']
        );
        $this->assertSame([3, 1], $sql[0]['bindings']);
        $this->assertIsFloat($sql[0]['time']);
    }

    /**
     * @test
     */
    public function test_nothing_gets_run_for_deletes(): void
    {
        $connection = $this->getMysqliConnection();

        $count = $connection->table('cities')->where('country_id', 1)->count();
        $this->assertSame(2, $count);

        $sql = $connection->pretend(function (MysqliConnection $connection) {
            $result = $connection->table('cities')->where('country_id', 1)->delete();
            $this->assertSame(0, $result);
        });

        // No records have been deleted
        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());
        $this->assertSame(
            'delete from `wp_cities` where `country_id` = ?',
            $sql[0]['query']
        );
        $this->assertSame([1], $sql[0]['bindings']);
        $this->assertIsFloat($sql[0]['time']);
    }

    /**
     * @test
     */
    public function test_nothing_gets_run_for_unprepared_queries(): void
    {
        $connection = $this->getMysqliConnection();

        $count = $connection->table('cities')->where('country_id', 1)->count();
        $this->assertSame(2, $count);

        $sql = $connection->pretend(function (MysqliConnection $connection) {
            $result = $connection->unprepared(
                'delete from wp_cities where country_id = 1'
            );
            $this->assertTrue($result);
        });

        // No records have been deleted
        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());
        $this->assertSame(
            'delete from wp_cities where country_id = 1',
            $sql[0]['query']
        );
        $this->assertSame([], $sql[0]['bindings']);
        $this->assertIsFloat($sql[0]['time']);
    }

    /**
     * @test
     */
    public function test_nothing_gets_run_for_cursor_selects(): void
    {
        $connection = $this->getMysqliConnection();

        $sql = $connection->pretend(function (MysqliConnection $connection) {
            $records = $connection->table('cities')
                ->whereIn('country_id', [1, 2])
                ->cursor();

            $this->assertInstanceOf(LazyCollection::class, $records);

            $names = [];

            foreach ($records as $record) {
                $names = $record['name'];
                $this->fail('generator should be have been empty.');
            }

            $this->assertSame([], $names);
        });

        $this->assertSame(
            'select * from `wp_cities` where `country_id` in (?, ?)',
            $sql[0]['query']
        );
        $this->assertSame([1, 2], $sql[0]['bindings']);
        $this->assertIsFloat($sql[0]['time']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();
        $wp_eloquent = new WPEloquentStandalone();
        $wp_eloquent->bootstrap();

        $this->withNewTables();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }
}
