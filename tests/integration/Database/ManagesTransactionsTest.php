<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use BetterWP\Database\BetterWPDb;
    use BetterWP\Database\FakeDB;
    use BetterWP\Database\WPConnection;
    use BetterWP\Database\Contracts\ConnectionResolverInterface;
    use Mockery as m;
    use mysqli_sql_exception;

    class ManagesTransactionsTest extends DatabaseTestCase
    {

        protected $defer_boot = true;

        /**
         * @var BetterWPDb
         */
        private $wpdb;

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

        private function newWpTransactionConnection() : WPConnection
        {

            $this->wpdb         = m::mock( FakeDB::class );
            $this->wpdb->prefix = 'wp_';
            $this->wpdb->dbname = 'wp_eloquent';
            $this->wpdb->shouldReceive( 'check_connection' )->andReturn( true )->byDefault();

            return new WpConnection( $this->wpdb );

        }

        /**
         *
         *
         *
         *
         *
         * MANUAL TRANSACTIONS
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function the_transaction_level_does_not_increment_when_an_exception_is_thrown() {

            $connection = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'check_connection' )->andReturnFalse();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()
                       ->andThrow( mysqli_sql_exception::class );

            // $this->error_handler->shouldReceive( 'handle' )
            //                     ->once()
            //                     ->with( m::type( QueryException::class ) )
            //                     ->andThrow( Exception::class, 'Ups' );

            try {

                $connection->beginTransaction();

            }

            catch ( Throwable $e ) {

                $this->assertSame( 'Ups', $e->getMessage() );

                $this->assertEquals( 0, $connection->transactionLevel() );

            }


        }

        /** @test */
        public function begin_transaction_reconnects_on_lost_connection() {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()
                       ->andThrows( new mysqli_sql_exception( 'the server has gone away | TEST ' ) );
            $this->wpdb->shouldReceive( 'startTransaction' );
            $this->wpdb->shouldReceive( 'createSavePoint' )->once()->with( 'SAVEPOINT trans1' );

            $wp->beginTransaction();

            self::assertSame( 1, $wp->transactionLevel() );

        }

        /** @test */
        public function if_an_exception_occurs_during_the_beginning_of_a_transaction_we_try_again_only_for_connection_errors() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()
                       ->andThrow( mysqli_sql_exception::class, 'Something weird happened | TEST ' );

            $this->error_handler->shouldReceive( 'handle' )->once()
                                ->andThrow( Exception::class, 'Ups' );

            try {
                $wp->beginTransaction();
            }
            catch ( Throwable $e ) {

                $this->assertEquals( 0, $wp->transactionLevel() );
                $this->assertSame( 'Ups', $e->getMessage() );

            }
        }

        /** @test */
        public function if_we_fail_once_beginning_a_transaction_but_succeed_the_second_time_the_count_is_increased() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()
                       ->andThrows( new mysqli_sql_exception( 'server has gone away | TEST ' ) );
            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );

            try {

                $wp->beginTransaction();

                $this->assertEquals( 1, $wp->transactionLevel() );


            }
            catch ( Exception $e ) {

                $this->fail( 'Unexpected Exception: ' . $e->getMessage() );


            }


        }

        /** @test */
        public function different_save_points_can_be_created_manually() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );

            try {

                $wp->beginTransaction();

                $wp->savepoint();

                $this->assertEquals( 2, $wp->transactionLevel() );


            }
            catch ( Exception $e ) {

                $this->fail( 'Unexpected Exception: ' . $e->getMessage() );


            }

        }

        /** @test */
        public function a_transaction_can_be_committed_manually() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );

            $this->wpdb->shouldReceive( 'commitTransaction' )->once();

            try {

                $wp->beginTransaction();

                $this->assertEquals( 1, $wp->transactionLevel() );

                $wp->commit();

                $this->assertEquals( 0, $wp->transactionLevel() );

            }
            catch ( Exception $e ) {

                $this->fail( 'Unexpected Exception: ' . $e->getMessage() );


            }

        }

        /** @test */
        public function a_transaction_can_be_committed_manually_with_several_custom_savepoints() {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldReceive( 'commitTransaction' )->once();

            try {

                $wp->beginTransaction();
                $wp->savepoint();

                $this->assertEquals( 2, $wp->transactionLevel() );

                $wp->commit();

                $this->assertEquals( 0, $wp->transactionLevel() );

            }
            catch ( Exception $e ) {

                $this->fail( 'Unexpected Exception: ' . $e->getMessage() );


            }

        }

        /** @test */
        public function manual_rollbacks_restore_the_latest_save_point_by_default() {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans3' );
            $this->wpdb->shouldReceive( 'rollbackTransaction' )->once()
                       ->with( 'ROLLBACK TO SAVEPOINT trans3' );

            $wp->beginTransaction();
            $wp->savepoint();
            $wp->savepoint();

            // error happens here.

            $wp->rollBack();

            self::assertEquals( 2, $wp->transactionLevel() );


        }

        /** @test */
        public function nothing_happens_if_an_invalid_level_is_provided_for_rollbacks() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldNotReceive( 'rollbackTransaction' );

            $wp->beginTransaction();
            $wp->savepoint();

            $this->assertEquals( 2, $wp->transactionLevel() );

            $wp->rollBack( - 4 );
            $wp->rollBack( 3 );


        }

        /** @test */
        public function manual_rollbacks_to_custom_levels_work() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans3' );
            $this->wpdb->shouldReceive( 'rollbackTransaction' )->once()
                       ->with( 'ROLLBACK TO SAVEPOINT trans2' );

            $wp->beginTransaction();
            $wp->savepoint();
            $wp->savepoint();

            // error happens here.

            $wp->rollBack( 2 );

            self::assertEquals( 1, $wp->transactionLevel() );

        }

        /** @test */
        public function the_savepoint_methods_serves_as_an_alias_for_begin_transaction() {


            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans3' );
            $this->wpdb->shouldReceive( 'rollbackTransaction' )->once()
                       ->with( 'ROLLBACK TO SAVEPOINT trans2' );

            $wp->beginTransaction();
            $wp->savepoint();
            $wp->savepoint();

            // error happens here.

            $wp->rollBack( 2 );

            self::assertEquals( 1, $wp->transactionLevel() );


        }

        /** @test */
        public function interacting_with_several_custom_savepoints_manually_works() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldNotReceive( 'createSavepoint' )->with( 'SAVEPOINT trans3' );

            $this->wpdb->shouldReceive( 'rollbackTransaction' )->once()
                       ->with( 'ROLLBACK TO SAVEPOINT trans2' );

            $wp->beginTransaction();

            $this->wpdb->shouldReceive( 'doStatement' )->once()->andReturnTrue();
            $this->wpdb->shouldReceive( 'doStatement' )->once()
                       ->andThrow( mysqli_sql_exception::class );
            $this->wpdb->shouldNotReceive( 'doAffectingStatement' );

            $this->error_handler->shouldReceive( 'handle' )->andThrow( Exception::class, 'Ups' );

            try {

                $wp->insert( 'foobar', [ 'foo' ] );

                $wp->savepoint();

                // ERROR HERE
                $wp->insert( 'bizbar', [ 'foo' ] );

                $wp->savepoint();

                $wp->update( 'foobar', [ 'biz' ] );

            }

            catch ( Exception $e ) {

                $wp->rollBack();

                $this->assertSame( 'Ups', $e->getMessage() );

                $this->assertSame( 1, $wp->transactionLevel() );

            }


        }

        /** @test */
        public function the_transaction_can_be_rolled_back_completely_when_if_zero_is_provided() {

            $wp = $this->newWpTransactionConnection();

            $this->wpdb->shouldReceive( 'startTransaction' )->once()->andReturnNull();
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans1' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans2' );
            $this->wpdb->shouldReceive( 'createSavepoint' )->once()->with( 'SAVEPOINT trans3' );
            $this->wpdb->shouldReceive( 'rollbackTransaction' )->once()->with( "ROLLBACK" );

            $wp->beginTransaction();
            $wp->savepoint();
            $wp->savepoint();

            // error happens here.

            $wp->rollBack( 0 );

            self::assertEquals( 0, $wp->transactionLevel() );

        }


    }