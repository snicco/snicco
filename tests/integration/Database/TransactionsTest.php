<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use BetterWP\Database\WPConnection;
    use Illuminate\Database\QueryException;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use mysqli;

    class TransactionsTest extends DatabaseTestCase
    {

        /**
         *
         * We use a separate mysqli connection to verify that our transactions indeed work as
         * expected.
         *
         * @var mysqli
         */
        private $verification_connection;

        protected function setUp() : void
        {

            $this->afterApplicationCreated(function () {

                $this->createInitialTable();
                $this->removeWpBrowserTransaction();

            });
            parent::setUp();

            $this->verification_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        }

        protected function tearDown() : void
        {

            $this->dropInitialTable();

            parent::tearDown();
        }

        private function createInitialTable()
        {

            if ( ! Schema::hasTable('football_teams')) {

                Schema::create('football_teams', function (Blueprint $table) {

                    $table->bigIncrements('id');
                    $table->string('name')->unique();
                    $table->string('country');

                });

                DB::table('football_teams')->insert([
                    ['name' => 'Real Madrid', 'country' => 'spain'],
                    ['name' => 'Borussia Dortmund', 'country' => 'germany'],
                    ['name' => 'Bayern Munich', 'country' => 'germany'],
                ]);

            }

        }

        private function dropInitialTable()
        {

            if (Schema::hasTable('football_teams')) {

                Schema::drop('football_teams');

            }


        }

        /** @test */
        public function a_basic_manual_transaction_works()
        {

            DB::beginTransaction();

            $db = $this->assertDbTable('wp_football_teams');
            $db->assertRecordNotExists(['name' => 'FC Barcelona']);

            DB::connection()->table('football_teams')->insert([
                ['name' => 'FC Barcelona', 'country' => 'spain'],
            ]);

            $this->assertTeamNotExists('FC Barcelona');

            DB::commit();

            $this->assertTeamExists('FC Barcelona');


        }

        /** @test */
        public function a_manual_transaction_can_be_rolled_back()
        {

            DB::beginTransaction();

            try {

                DB::table('football_teams')->insert([
                    ['name' => 'Liverpool', 'country' => 'england'],
                ]);

                // will throw non unique error
                DB::table('football_teams')->insert([
                    ['name' => 'Real Madrid', 'country' => 'spain'],
                ]);

            }
            catch (QueryException $e) {

                DB::rollback();
                $this->assertTeamNotExists('Liverpool');

            }


        }

        /** @test */
        public function you_can_rollback_to_custom_savepoints_manually()
        {


            DB::beginTransaction();

            try {

                DB::table('football_teams')->insert([
                    ['name' => 'Liverpool', 'country' => 'england'],
                ]);

                // Savepoint 2
                DB::savepoint();

                DB::table('football_teams')->insert([
                    ['name' => 'Chelsea', 'country' => 'england'],
                ]);

                // Savepoint 3
                DB::savepoint();

                DB::table('football_teams')->insert([
                    ['name' => 'Sevilla', 'country' => 'spain'],
                ]);

                // will throw non unique error
                DB::table('football_teams')->insert([
                    ['name' => 'Real Madrid', 'country' => 'spain'],
                ]);


            }
            catch (QueryException $e) {


                DB::rollback(3);

                DB::commit();

                $this->assertTeamExists('Liverpool');
                $this->assertTeamExists('Chelsea');
                $this->assertTeamNotExists('Sevilla');

            }


        }

        /** @test */
        public function automatic_transactions_work_when_no_errors_occur()
        {


            DB::transaction(function (WPConnection $connection) {

                $connection->table('football_teams')->insert([
                    ['name' => 'Liverpool', 'country' => 'england'],
                    ['name' => 'Chelsea', 'country' => 'england'],
                    ['name' => 'Arsenal', 'country' => 'england'],
                ]);

            });

            $this->assertTeamExists('Liverpool');
            $this->assertTeamExists('Chelsea');
            $this->assertTeamExists('Arsenal');

        }

        /** @test */
        public function automatic_transactions_get_rolled_back_when_a_sql_error_occurs()
        {

            try {

                DB::transaction(function (WpConnection $connection) {

                    $connection->table('football_teams')->insert([
                        ['name' => 'Liverpool', 'country' => 'england'],
                        ['name' => 'Chelsea', 'country' => 'england'],
                        ['name' => 'Arsenal', 'country' => 'england'],
                        ['name' => 'Real Madrid', 'country' => 'spain'],
                        // Will force duplicate key error.
                    ]);

                });

                $this->fail('Expected query exception was not thrown.');

            }
            catch (QueryException $e) {

                $this->assertStringContainsString(
                    "Duplicate entry 'Real Madrid' for key 'wp_football_teams.football_teams_name_unique",
                    $e->getMessage()
                );

                // From our test setup
                $this->assertTeamExists('Real Madrid');

                $this->assertTeamNotExists('Chelsea');
                $this->assertTeamNotExists('Arsenal');
                $this->assertTeamNotExists('Arsenal');

            }


        }

        /** @test */
        public function any_exception_will_cause_the_transaction_to_be_rolled_back()
        {

            try {

                DB::transaction(function (WpConnection $connection) {

                    $connection->table('football_teams')->insert([
                        ['name' => 'Liverpool', 'country' => 'england'],
                        ['name' => 'Chelsea', 'country' => 'england'],
                        ['name' => 'Arsenal', 'country' => 'england'],
                    ]);

                    throw new \Exception('Validation failed | TEST');


                });

                $this->fail('Exception was not handled thrown');

            }
            catch (\Exception $e) {

                $this->assertStringContainsString(
                    "Validation failed | TEST",
                    $e->getMessage()
                );

                $this->assertTeamExists('Real Madrid');
                $this->assertTeamNotExists('Chelsea');
                $this->assertTeamNotExists('Arsenal');
                $this->assertTeamNotExists('Arsenal');

            }

        }

        private function assertTeamExists(string $team_name)
        {

            $result = $this->verification_connection->query("select count(*) as `count` from `wp_football_teams` where `name` = '$team_name'");
            $count = $result->fetch_object()->count;

            $this->assertSame('1', $count, "The team [$team_name] was not found.");

        }

        private function assertTeamNotExists(string $team_name)
        {

            $result = $this->verification_connection->query("select count(*) as `count` from `wp_football_teams` where `name` = '$team_name'");
            $count = $result->fetch_object()->count;

            $this->assertSame('0', $count, 'The team: '.$team_name.' was not found.');

        }


    }