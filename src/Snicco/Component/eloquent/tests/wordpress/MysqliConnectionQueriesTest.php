<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\LazyCollection;
use InvalidArgumentException;
use Snicco\Component\Eloquent\Illuminate\MysqliConnection;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WithTestTables;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WPDBTestHelpers;
use Snicco\Component\Eloquent\WPEloquentStandalone;

/**
 * @internal
 */
final class MysqliConnectionQueriesTest extends WPTestCase
{
    use WPDBTestHelpers;
    use WithTestTables;

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

    /**
     * @test
     */
    public function test_select_one_works(): void
    {
        $record = DB::connection()->selectOne('select `name` from `wp_cities` where `country_id` = ? limit 1', [1]);

        $this->assertIsObject($record);
        $this->assertSame('berlin', $record->name);
    }

    /**
     * @test
     */
    public function test_select_works(): void
    {
        $records = DB::table('cities')
            ->select('name')
            ->where('country_id', 1)
            ->get()
            ->toArray();

        $this->assertCount(2, $records);
        $this->assertEquals((object) [
            'name' => 'berlin',
        ], $records[0]);
        $this->assertEquals((object) [
            'name' => 'munich',
        ], $records[1]);
    }

    /**
     * @test
     */
    public function test_select_from_write_connection_is_just_an_alias_for_select(): void
    {
        $records = $this->getMysqliConnection()
            ->selectFromWriteConnection('select `name` from `wp_cities` where `country_id` = ? limit 1', [2]);

        $this->assertEquals((object) [
            'name' => 'madrid',
        ], $records[0]);
    }

    /**
     * @test
     */
    public function test_inserts_work(): void
    {
        $this->assertSame(2, DB::table('cities')->where('country_id', 2)->count());

        $success = DB::table('cities')->insert([
            [
                'name' => 'seville',
                'country_id' => 2,
                'population' => 1,
            ],
        ]);

        $this->assertTrue($success);

        $this->assertSame(3, DB::table('cities')->where('country_id', 2)->count());
    }

    /**
     * @test
     */
    public function test_insert_get_id_works(): void
    {
        $id = DB::table('cities')->insertGetId([
            'name' => 'seville',
            'country_id' => 2,
            'population' => 1,
        ]);

        $this->assertIsNumeric($id);
    }

    /**
     * @test
     */
    public function test_updates_work(): void
    {
        $rows = DB::table('cities')
            ->where('country_id', 2)
            ->update([
                'population' => 5,
            ]);

        $this->assertSame(2, $rows);

        $madrid = DB::table('cities')->where('name', 'madrid')->select('population')->first();

        $this->assertSame($madrid->population, 5);
    }

    /**
     * @test
     */
    public function updates_with_no_affected_rows_return_zero(): void
    {
        $rows = DB::table('cities')
            ->where('country_id', 10)
            ->update([
                'population' => 5,
            ]);

        $this->assertSame(0, $rows);
    }

    /**
     * @test
     */
    public function deletes_return_the_number_of_deleted_records(): void
    {
        $connection = $this->getMysqliConnection();

        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());

        $deleted = $connection->table('cities')
            ->where('name', 'berlin')
            ->delete();

        $this->assertSame(1, $deleted);

        $this->assertSame(1, $connection->table('cities')->where('country_id', 1)->count());
    }

    /**
     * @test
     */
    public function test_returns_zero_for_non_matching_deletes(): void
    {
        $connection = $this->getMysqliConnection();

        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());

        // frankfurt does not exist in our db fixtures
        $deleted = $connection->table('cities')
            ->where('name', 'frankfurt')
            ->delete();

        $this->assertSame(0, $deleted);

        $this->assertSame(2, $connection->table('cities')->where('country_id', 1)->count());
    }

    /**
     * @test
     */
    public function test_unprepared_queries_work(): void
    {
        $connection = $this->getMysqliConnection();

        $result = $connection->unprepared('update wp_cities set population = 3 where country_id = 1');

        $this->assertTrue($result);

        $this->assertSame(
            2,
            $connection->table('cities')
                ->where([
                    'country_id' => 1,
                    'population' => 3,
                ])->count()
        );
    }

    /**
     * @test
     */
    public function test_raw_works(): void
    {
        $connection = $this->getMysqliConnection();

        $record = $connection->table('cities')
            ->select($connection->raw('count(*) as cities_in_germany'))
            ->where('country_id', 1)
            ->first();

        $this->assertSame(2, $record->cities_in_germany);
    }

    /**
     * @test
     */
    public function test_cursor_select(): void
    {
        $records = $this->getMysqliConnection()
            ->table('cities')
            ->whereIn('country_id', [1, 2])
            ->cursor();

        $this->assertInstanceOf(LazyCollection::class, $records);

        $names = [];

        foreach ($records as $record) {
            $names[] = $record['name'];
        }

        $this->assertSame(['berlin', 'munich', 'madrid', 'barcelona'], $names);
    }

    /**
     * @test
     */
    public function test_cursor_with_empty_results(): void
    {
        $records = $this->getMysqliConnection()
            ->table('cities')
            ->whereIn('country_id', [10, 11])
            ->cursor();

        $this->assertInstanceOf(LazyCollection::class, $records);

        $names = [];

        foreach ($records as $record) {
            $names = $record['name'];
            $this->fail('generator should be have been empty.');
        }

        $this->assertSame([], $names);
    }

    /**
     * @test
     */
    public function test_cursor_when_pretending(): void
    {
        $connection = $this->getMysqliConnection();
        $sql = $connection->pretend(function (MysqliConnection $connection): void {
            $records = $connection->table('cities')
                ->whereIn('country_id', [1, 2])
                ->cursor();

            $this->assertInstanceOf(LazyCollection::class, $records);

            foreach ($records as $record) {
                $this->fail('generator should have been empty');
            }
        });

        $this->assertSame('select * from `wp_cities` where `country_id` in (?, ?)', $sql[0]['query']);
        $this->assertSame([1, 2], $sql[0]['bindings']);
    }

    private function getMysqliConnection(): MysqliConnection
    {
        $c = DB::connection();
        if (! $c instanceof MysqliConnection) {
            throw new InvalidArgumentException('Wrong connection type.');
        }

        return $c;
    }
}
