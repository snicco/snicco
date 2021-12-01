<?php

declare(strict_types=1);

namespace Tests\Database\integration;

use mysqli;
use Exception;
use RuntimeException;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Snicco\Database\MysqliConnection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Snicco\Database\WPEloquentStandalone;
use Tests\Database\helpers\WPDBTestHelpers;

class TransactionsTest extends WPTestCase
{
    
    use WPDBTestHelpers;
    
    /**
     * We use a separate mysqli connection to verify that our transactions indeed work as
     * expected since the same mysqli instance will always have access to the data inside the
     * transaction.
     *
     * @var mysqli
     */
    private $verification_connection;
    
    protected function setUp() :void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();
        
        $this->removeWpBrowserTransaction();
        $this->verification_connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        (new WPEloquentStandalone())->bootstrap();
        $this->createInitialTable();
    }
    
    protected function tearDown() :void
    {
        $this->dropInitialTable();
        parent::tearDown();
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
        
        // This runs with the same mysqli connection as the DB facade so the transaction data is already present.
        $db->assertRecordExists(['name' => 'FC Barcelona']);
        
        // This runs with our verification mysqli instance which does not yet have the transaction data.
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
            
            // will throw non-unique error
            DB::table('football_teams')->insert([
                ['name' => 'Real Madrid', 'country' => 'spain'],
            ]);
        } catch (QueryException $e) {
            DB::rollback();
            $db = $this->assertDbTable('wp_football_teams');
            $db->assertRecordNotExists(['name' => 'Liverpool']);
        }
    }
    
    /** @test */
    public function nested_transactions_work()
    {
        try {
            DB::transaction(function () {
                DB::transaction(function () {
                    DB::table('football_teams')
                      ->where('name', 'Real Madrid')->delete();
                });
                throw new RuntimeException("test exception");
            });
        } catch (RuntimeException $e) {
            $this->assertSame('test exception', $e->getMessage());
        }
        finally {
            $this->assertTeamExists('Real Madrid');
        }
    }
    
    /** @test */
    public function automatic_transactions_work_when_no_errors_occur()
    {
        DB::transaction(function (MysqliConnection $connection) {
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
            DB::transaction(function (MysqliConnection $connection) {
                $connection->table('football_teams')->insert([
                    ['name' => 'Liverpool', 'country' => 'england'],
                    ['name' => 'Chelsea', 'country' => 'england'],
                    ['name' => 'Arsenal', 'country' => 'england'],
                    ['name' => 'Real Madrid', 'country' => 'spain'],
                    // Will force duplicate key error.
                ]);
            });
            
            $this->fail('Expected query exception was not thrown.');
        } catch (QueryException $e) {
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
            DB::transaction(function (MysqliConnection $connection) {
                $connection->table('football_teams')->insert([
                    ['name' => 'Liverpool', 'country' => 'england'],
                    ['name' => 'Chelsea', 'country' => 'england'],
                    ['name' => 'Arsenal', 'country' => 'england'],
                ]);
                
                throw new Exception('Validation failed | TEST');
            });
            
            $this->fail('Exception was not handled thrown');
        } catch (Exception $e) {
            $this->assertStringContainsString(
                "Validation failed | TEST",
                $e->getMessage()
            );
            
            // From our test setup
            $this->assertTeamExists('Real Madrid');
            
            $this->assertTeamNotExists('Chelsea');
            $this->assertTeamNotExists('Arsenal');
            $this->assertTeamNotExists('Arsenal');
        }
    }
    
    private function assertTeamNotExists(string $team_name)
    {
        $result = $this->verification_connection->query(
            "select count(*) as `count` from `wp_football_teams` where `name` = '$team_name'"
        );
        $count = $result->fetch_object()->count;
        
        $this->assertSame('0', $count, 'The team: '.$team_name.' was found.');
    }
    
    private function assertTeamExists(string $team_name)
    {
        $result = $this->verification_connection->query(
            "select count(*) as `count` from `wp_football_teams` where `name` = '$team_name'"
        );
        $count = $result->fetch_object()->count;
        
        $this->assertSame('1', $count, "The team [$team_name] was not found.");
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
    
}