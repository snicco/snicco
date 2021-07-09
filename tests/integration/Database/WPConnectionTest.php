<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use BetterWP\Database\Contracts\BetterWPDbInterface;
    use BetterWP\Database\Illuminate\MySqlSchemaBuilder;
    use BetterWP\Database\WPConnection;
    use BetterWP\Database\Contracts\ConnectionResolverInterface;
    use BetterWP\Database\Illuminate\MySqlQueryGrammar;
    use Exception;
    use Illuminate\Database\Query\Builder;
    use Illuminate\Database\Query\Processors\MySqlProcessor;
    use Illuminate\Database\QueryException;
    use Illuminate\Database\Schema\Grammars\MySqlGrammar as MySqlSchemaGrammar;

    class WPConnectionTest extends DatabaseTestCase
    {

        protected $defer_boot = true;

        protected function setUp() : void
        {

            $this->afterLoadingConfig(function () {

                $this->withFakeDb();
            });
            parent::setUp();
        }

        private function newWpConnection(string $name = null) : WPConnection
        {

            $this->boot();
            $resolver = $this->app->resolve(ConnectionResolverInterface::class);

            return $resolver->connection($name);

        }

        private function mockDb()
        {

            $m = \Mockery::mock(BetterWPDbInterface::class);
            $m->dbname = 'testing';
            $m->prefix = 'wp_';

            return $m;

        }

        /**
         *
         *
         *
         *
         *
         * INSTANCE SETUP
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function constructing_the_wp_connection_correctly_sets_up_all_collaborators()
        {

            $connection = $this->newWpConnection();

            $query_grammar = $connection->getQueryGrammar();
            $this->assertInstanceOf(MySqlQueryGrammar::class, $query_grammar);
            $this->assertSame('wp_', $query_grammar->getTablePrefix());

            $schema_grammar = $connection->getSchemaGrammar();
            $this->assertInstanceOf(MySqlSchemaGrammar::class, $schema_grammar);
            $this->assertSame('wp_', $schema_grammar->getTablePrefix());

            $processor = $connection->getPostProcessor();
            $this->assertInstanceOf(MySqlProcessor::class, $processor);


        }

        /** @test */
        public function the_query_builder_uses_the_correct_grammar_and_processor()
        {

            $wp_connection = $this->newWpConnection();

            $query_builder = $wp_connection->query();

            self::assertInstanceOf(Builder::class, $query_builder);

            self::assertSame($wp_connection->getPostProcessor(), $query_builder->processor);
            self::assertSame($wp_connection->getQueryGrammar(), $query_builder->grammar);


        }

        /** @test */
        public function the_schema_builder_uses_the_correct_grammar_and_processor()
        {

            $wp_connection = $this->newWpConnection();

            $schema_builder = $wp_connection->getSchemaBuilder();

            self::assertInstanceOf(MySqlSchemaBuilder::class, $schema_builder);


        }

        /** @test */
        public function the_connection_can_begin_a_query_against_a_query_builder_table()
        {

            $wp_connection = $this->newWpConnection();

            $query_builder = $wp_connection->table('wp_users', 'users');

            self::assertInstanceOf(Builder::class, $query_builder);

            self::assertSame('wp_users as users', $query_builder->from);


        }

        /** @test */
        public function bindings_get_prepared_correctly()
        {

            $result = $this->newWpConnection()->prepareBindings([

                true,
                false,
                'string',
                10,
                new \DateTime('07.04.2021 15:00'),

            ]);

            self::assertSame([

                1,
                0,
                'string',
                10,
                '2021-04-07 15:00:00',

            ], $result);

        }

        /**
         *
         *
         *
         *
         *
         * QUERIES
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function selecting_one_result_works_with_a_valid_query()
        {

            $wp = new WPConnection($m = $this->mockDb());
            $m->shouldReceive('doSelect')->once()
              ->with(
                  "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1",
                  ['calvin', 1])
              ->andReturn([

                  ['user_id' => 1, 'user_name' => 'calvin'],
                  ['user_id' => 2, 'user_name' => 'marlon'],

              ]);

            $query = "select * from `wp_users` where `user_name` = ? and `id` = ? limit 1";

            $result = $wp->selectOne($query, ['calvin', 1]);

            $this->assertSame($result, ['user_id' => 1, 'user_name' => 'calvin']);

        }

        /** @test */
        public function selecting_a_set_of_records_works_with_a_valid_query()
        {

            $this->withFakeDb();
            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnSelect(['foo' => 'bar']);

            $builder = $connection->table('customer')
                          ->select([
                              'first_name',
                              'last_name',
                          ])
                          ->where('first_name', 'calvin');

            $connection->select($builder->toSql(), $builder->getBindings());

            $assertable->assertDidSelect("select `first_name`, `last_name` from `wp_customer` where `first_name` = ?", ['calvin']);


        }

        /** @test */
        public function select_from_write_connection_is_just_an_alias_for_select()
        {

            $this->withFakeDb();
            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnSelect(['foo' => 'bar']);
            $builder = $connection->table('customer')
                          ->select([
                              'customer_id',
                              'first_name',
                              'last_name',
                          ])
                          ->where('first_name', 'MARY')
                          ->where('last_name', 'JONES');

            $connection->selectFromWriteConnection($builder->toSql(), $builder->getBindings());

            $assertable->assertDidSelect(
                "select `customer_id`, `first_name`, `last_name` from `wp_customer` where `first_name` = ? and `last_name` = ?",
                ['MARY', 'JONES']
            );

        }

        /** @test */
        public function successful_inserts_return_true()
        {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnInsert(true);

            $success = $connection->table('customer')->insert([

                ['customer_id' => 1, 'store_id' => 1, 'first_name' => 'calvin'],
                ['customer_id' => 2, 'store_id' => 2, 'first_name' => 'marlon'],

            ]);
            $this->assertTrue($success);

            $assertable->assertDidInsert(
                "insert into `wp_customer` (`customer_id`, `first_name`, `store_id`) values (?, ?, ?), (?, ?, ?)",
                [1, 'calvin', 1, 2, 'marlon', 2]
            );

        }

        /** @test */
        public function insert_without_affected_rows_return_false()
        {


            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnInsert(false);

            $success = $connection->table('customer')->insert([

                ['customer_id' => 1, 'store_id' => 1, 'first_name' => 'calvin'],
                ['customer_id' => 2, 'store_id' => 2, 'first_name' => 'marlon'],

            ]);
            $this->assertFalse($success);

            $assertable->assertDidInsert(
                "insert into `wp_customer` (`customer_id`, `first_name`, `store_id`) values (?, ?, ?), (?, ?, ?)",
                [1, 'calvin', 1, 2, 'marlon', 2]
            );


        }

        /** @test */
        public function successful_updates_return_the_number_of_affected_rows()
        {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnUpdate(1);

            $safe_sql = "update `wp_customer` set `first_name` = ? where `customer_id` = ?";

            $affected_rows = $connection->table('customer')
                                        ->where('customer_id', 1)
                                        ->update(['first_name' => 'calvin']);

            $assertable->assertDidUpdate($safe_sql, ['calvin', 1]);
            $this->assertSame(1, $affected_rows);
        }

        /** @test */
        public function updates_with_no_affected_rows_return_zero()
        {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnUpdate(0);

            $safe_sql = "update `wp_customer` set `first_name` = ? where `customer_id` = ?";

            $affected_rows = $connection->table('customer')
                                        ->where('customer_id', 1)
                                        ->update(['first_name' => 'calvin']);

            $assertable->assertDidUpdate($safe_sql, ['calvin', 1]);
            $this->assertSame(0, $affected_rows);

        }

        /** @test */
        public function deletes_return_the_amount_of_deleted_rows()
        {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnDelete(2);

            $deleted_rows = $connection->table('customer')
                                       ->where('customer_id', '<', 3)
                                       ->delete();

            $assertable->assertDidDelete(
                "delete from `wp_customer` where `customer_id` < ?",
                [3]
            );

            $this->assertEquals(2, $deleted_rows);

        }

        /** @test */
        public function zero_gets_returned_if_no_row_got_deleted()
        {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnDelete(0);

            $deleted_rows = $connection->table('customer')
                                       ->where('customer_id', '<', 3)
                                       ->delete();

            $assertable->assertDidDelete(
                "delete from `wp_customer` where `customer_id` < ?",
                [3]
            );

            $this->assertEquals(0, $deleted_rows);

        }

        /** @test */
        public function unprepared_queries_are_run_without_preparing()
        {

            $this->withFakeDb();
            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnUnprepared(true);

            $success = $connection->unprepared($sql = "select `first_name`, `last_name` from `wp_customer` where `first_name` = 'calvin'");

            $assertable->assertDidUnprepared($sql);
            $this->assertTrue($success);

        }

        /** @test */
        public function testCursorSelect()
        {

            $this->withFakeDb();
            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnCursor($m = \Mockery::mock(\mysqli_result::class));

            $m->shouldReceive('fetch_assoc')->once()->andReturn(['foo' => 'bar']);
            $m->shouldReceive('fetch_assoc')->once()->andReturn(['bar' => 'baz']);
            $m->shouldReceive('fetch_assoc')->andReturn(null);

            $builder = $connection->table('foo')
                                  ->where('first_name', 'calvin')->cursor();

            $results = [];

            $assertable->assertDidNotDoCursorSelect();

            foreach ($builder as $item) {

                $results[] = $item;

            }

            $this->assertSame([
                ['foo' => 'bar'],
                ['bar' => 'baz'],
            ], $results);

            $assertable->assertDidCursorSelect("select * from `wp_foo` where `first_name` = ?", ['calvin']);

        }

        /**
         *
         *
         *
         *
         *
         * PRETEND MODE
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function nothing_gets_executed_for_selects()
        {

            $connection = $this->newWpConnection();

            $queries = $connection->pretend(function ($connection) {

                $result1 = $connection->select('foo bar', ['baz', true]);
                $result2 = $connection->select('biz baz', ['boo', false]);

                $this->assertSame([], $result1);
                $this->assertSame([], $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($connection)->assertDidNotDoSelect();

        }

        /** @test */
        public function nothing_gets_executed_for_select_one()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->selectOne('foo bar', ['baz', true]);
                $result2 = $wp->selectOne('biz baz', ['boo', false]);

                $this->assertSame([], $result1);
                $this->assertSame([], $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoSelect();

        }

        /** @test */
        public function nothing_gets_executed_for_inserts()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->insert('foo bar', ['baz', true]);
                $result2 = $wp->insert('biz baz', ['boo', false]);

                $this->assertSame(true, $result1);
                $this->assertSame(true, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoStatement();

        }

        /** @test */
        public function nothing_gets_executed_for_updates()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->update('foo bar', ['baz', true]);
                $result2 = $wp->update('biz baz', ['boo', false]);

                $this->assertSame(0, $result1);
                $this->assertSame(0, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoUpdate();

        }

        /** @test */
        public function nothing_gets_executed_for_deletes()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->delete('foo bar', ['baz', true]);
                $result2 = $wp->delete('biz baz', ['boo', false]);

                $this->assertSame(0, $result1);
                $this->assertSame(0, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoDelete();
        }

        /** @test */
        public function nothing_gets_executed_for_unprepared_queries()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function ($wp) {

                $result1 = $wp->unprepared('foo bar', ['baz', true]);
                $result2 = $wp->unprepared('biz baz', ['boo', false]);

                $this->assertSame(true, $result1);
                $this->assertSame(true, $result2);

            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals([], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals([], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoUnprepared();

        }

        /** @test */
        public function nothing_gets_executed_for_cursor_selects()
        {

            $wp = $this->newWpConnection();

            $queries = $wp->pretend(function (WpConnection $wp) {

                $result1 = $wp->cursor('foo bar', ['baz', true]);
                $result2 = $wp->cursor('biz baz', ['boo', false]);

                foreach ($result1 as $item) {

                    $this->fail('This should not execute');

                }
                foreach ($result2 as $item) {

                    $this->fail('This should not execute');

                }


            });

            $this->assertSame('foo bar', $queries[0]['query']);
            $this->assertEquals(['baz', 1], $queries[0]['bindings']);
            $this->assertTrue(is_float($queries[0]['time']));

            $this->assertSame('biz baz', $queries[1]['query']);
            $this->assertEquals(['boo', 0], $queries[1]['bindings']);
            $this->assertTrue(is_float($queries[1]['time']));

            $this->assertable($wp)->assertDidNotDoCursorSelect();

        }

        /**
         *
         *
         *
         *
         *
         * EXCEPTION HANDLING
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function errors_get_handled_for_selects() {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnSelect(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $connection->select('foobar', ['foo' => 'bar']);
                $this->fail('No query exception thrown');

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame( ['foo' => 'bar'], $e->getBindings() );

            }


        }

        /** @test */
        public function errors_get_handled_for_inserts() {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnInsert(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $connection->insert('foobar', ['foo' => 'bar']);
                $this->fail('No query exception thrown');

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame( ['foo' => 'bar'], $e->getBindings() );

            }


        }

        /** @test */
        public function errors_get_handled_for_updates() {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnUpdate(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $connection->update('foobar', ['foo' => 'bar']);
                $this->fail('No query exception thrown');

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame( ['foo' => 'bar'], $e->getBindings() );

            }


        }

        /** @test */
        public function errors_get_handled_for_deletes() {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnDelete(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $connection->delete('foobar', ['foo' => 'bar']);
                $this->fail('No query exception thrown');

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame( ['foo' => 'bar'], $e->getBindings() );

            }



        }

        /** @test */
        public function errors_get_handled_for_unprepared() {

            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnUnprepared(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $connection->unprepared('foobar');
                $this->fail('No query exception thrown');

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame( [], $e->getBindings() );

            }



        }

        /** @test */
        public function errors_get_handled_for_cursor_selects() {



            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnCursor(function () {
                throw new \mysqli_sql_exception();
            });

            try {

                $generator = $connection->cursor( 'foobar', ['foo' => 'bar'] );

                foreach ( $generator as $foo ) {

                    $this->fail( 'No Exception thrown' );

                }

            } catch (\Illuminate\Database\QueryException $e ) {

                $this->assertSame( 'foobar', $e->getSql() );
                $this->assertSame(  ['foo' => 'bar'], $e->getBindings() );

            }


        }

        /** @test */
        public function only_mysqli_exceptions_are_transformed_to_query_exceptions() {


            $connection = $this->newWpConnection();
            $assertable = $this->assertable($connection);
            $assertable->returnSelect(function () {
                throw new \Exception();
            });

            try {

                $connection->select( 'foo' );

                $this->fail( 'Wrong Exception type was handled' );

            }

            catch ( Exception $exception ) {

                $this->assertNotInstanceOf( QueryException::class, $exception );

            }

        }


    }