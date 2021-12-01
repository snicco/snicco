<?php

namespace Tests\Database\integration;

use Snicco\Support\Str;
use mysqli_sql_exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Container\Container;
use Codeception\TestCase\WPTestCase;
use Snicco\Database\MysqliConnection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert as PHPUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Snicco\Database\WPEloquentStandalone;
use Tests\Database\helpers\AssertableWpDB;
use Tests\Database\helpers\WPDBTestHelpers;
use Illuminate\Database\Schema\MySqlBuilder;

/**
 * NOTE: This TestClass is expecting a mysql version "^8.0.0".
 */
class SchemaBuilderTest extends WPTestCase
{
    
    use WPDBTestHelpers;
    
    /**
     * @var MySqlBuilder
     */
    private $builder;
    
    /**
     * @var MysqliConnection
     */
    private $mysqli_connection;
    
    protected function setUp() :void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();
        
        $this->withDatabaseExceptions();
        
        (new WPEloquentStandalone())->bootstrap();
        
        $this->builder = Schema::connection('wp_mysqli_connection');
        $this->mysqli_connection = DB::connection();
        
        if ($this->builder->hasTable('books')) {
            $this->builder->drop('books');
        }
        if ($this->builder->hasTable('authors')) {
            $this->builder->drop('authors');
        }
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        if ($this->builder->hasTable('books')) {
            $this->builder->drop('books');
        }
        
        if ($this->builder->hasTable('authors')) {
            $this->builder->drop('authors');
        }
    }
    
    /** @test */
    public function a_basic_table_can_be_created()
    {
        $this->assertFalse($this->builder->hasTable('books'));
        
        $this->builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
        
        $this->assertTrue($this->builder->hasTable('books'));
    }
    
    /** @test */
    public function table_existence_can_be_checked()
    {
        $this->assertFalse($this->builder->hasTable('books'));
        
        $this->builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
        
        $this->assertTrue($this->builder->hasTable('books'));
    }
    
    /** @test */
    public function all_table_names_can_be_retrieved()
    {
        $pluck = "Tables_in_".$this->mysqli_connection->getDatabaseName();
        $tables = collect($this->builder->getAllTables())->pluck($pluck)->toArray();
        
        $expected_core_tables = [
            0 => 'wp_commentmeta',
            1 => 'wp_comments',
            2 => 'wp_links',
            3 => 'wp_options',
            4 => 'wp_postmeta',
            5 => 'wp_posts',
            6 => 'wp_term_relationships',
            7 => 'wp_term_taxonomy',
            8 => 'wp_termmeta',
            9 => 'wp_terms',
            10 => 'wp_usermeta',
            11 => 'wp_users',
        ];
        
        foreach ($expected_core_tables as $expected_core_table) {
            $this->assertContains($expected_core_table, $tables);
        }
    }
    
    /**
     * General methods
     */
    
    /** @test */
    public function column_existence_can_be_checked()
    {
        $this->assertTrue($this->builder->hasColumn('users', 'user_login'));
        $this->assertFalse($this->builder->hasColumn('users', 'user_profile_pic'));
        
        $this->assertFalse(
            $this->builder->hasColumns('users', [
                'user_login',
                'user_profile_pic',
            ])
        );
        $this->assertTrue($this->builder->hasColumns('users', ['user_login', 'user_email']));
    }
    
    /** @test */
    public function a_table_can_be_dropped()
    {
        $this->builder->create('books2', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
        
        $this->assertTrue($this->builder->hasTable('books2'));
        
        $this->builder->drop('books2');
        
        $this->assertFalse($this->builder->hasTable('books2'));
    }
    
    /** @test */
    public function columns_can_be_dropped()
    {
        $this->builder->create('books', function (Blueprint $table) {
            $table->bigIncrements('book_id');
            $table->string('title');
            $table->integer('page_count');
            $table->timestamp('published');
            $table->longText('excerpt');
            $table->longText('bio');
        });
        
        $this->assertSame([
            0 => 'book_id',
            1 => 'title',
            2 => 'page_count',
            3 => 'published',
            4 => 'excerpt',
            5 => 'bio',
        ], $this->getColumnsByOrdinalPosition('books'));
        
        $this->builder->table('books', function (Blueprint $table) {
            $table->dropColumn(['title', 'published']);
            $table->dropColumn('bio');
        });
        
        $this->assertSame([
            0 => 'book_id',
            1 => 'page_count',
            2 => 'excerpt',
        ], $this->getColumnsByOrdinalPosition('books'));
        
        $this->builder->dropColumns('books', ['page_count', 'excerpt']);
        
        $this->assertSame([
            0 => 'book_id',
        ], $this->getColumnsByOrdinalPosition('books'));
    }
    
    /** @test */
    public function an_existing_column_can_be_renamed()
    {
        $this->builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email');
        });
        
        $this->builder->rename('books', 'books_new');
        
        $this->assertFalse($this->builder->hasTable('books'));
        $this->assertTrue($this->builder->hasTable('books_new'));
        
        $this->builder->drop('books_new');
    }
    
    /** @test */
    public function columns_can_be_added_to_an_existing_table()
    {
        $this->builder->create('books', function (Blueprint $table) {
            $table->bigIncrements('book_id');
        });
        
        $this->builder->table('books', function (Blueprint $table) {
            $table->string('title');
        });
        
        $this->assertSame([
            'book_id',
            'title',
        ], $this->getColumnsByOrdinalPosition('books'));
    }
    
    /** @test */
    public function big_increments_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->bigIncrements('id');
        });
        
        $builder->seeColumnOfType('id', 'bigint unsigned');
        
        $builder->seePrimaryKey('id');
    }
    
    /** @test */
    public function big_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->bigInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'bigint');
    }
    
    /** @test */
    public function binary_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->binary('photo');
        });
        
        $builder->seeColumnOfType('photo', 'blob');
    }
    
    /**
     * Creating column types
     */
    
    /** @test */
    public function boolean_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->boolean('confirmed');
        });
        
        $builder->seeColumnOfType('confirmed', 'tinyint(1)');
    }
    
    /** @test */
    public function char_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->char('name', 100);
            $table->char('email', 255);
        });
        
        $builder->seeColumnOfType('name', 'char(100)');
        $builder->seeColumnOfType('email', 'char(255)');
    }
    
    /** @test */
    public function date_time_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->dateTimeTz('created_at', 1);
            $table->dateTimeTz('created_at_precise', 2);
        });
        
        $builder->seeColumnOfType('created_at', 'datetime(1)');
        $builder->seeColumnOfType('created_at_precise', 'datetime(2)');
    }
    
    /** @test */
    public function date_time_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->dateTime('created_at', 1);
            $table->dateTime('created_at_precise', 2);
        });
        
        $builder->seeColumnOfType('created_at', 'datetime(1)');
        $builder->seeColumnOfType('created_at_precise', 'datetime(2)');
    }
    
    /** @test */
    public function date_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->date('date');
        });
        
        $builder->seeColumnOfType('date', 'date');
    }
    
    /** @test */
    public function decimal_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->decimal('money');
            $table->decimal('vote_count', 10, 3);
        });
        
        $builder->seeColumnOfType('money', 'decimal(8,2)');
        $builder->seeColumnOfType('vote_count', 'decimal(10,3)');
    }
    
    /** @test */
    public function double_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->double('money');
            $table->double('vote_count', 10, 3);
        });
        
        $builder->seeColumnOfType('money', 'double');
        $builder->seeColumnOfType('vote_count', 'double(10,3)');
    }
    
    /** @test */
    public function enum_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->enum('difficulty', ['easy', 'hard']);
        });
        
        $builder->seeColumnOfType('difficulty', "enum('easy','hard')");
    }
    
    /** @test */
    public function float_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->float('amount');
            $table->float('money', 10, 3);
        });
        
        $builder->seeColumnOfType('amount', 'double(8,2)');
        $builder->seeColumnOfType('money', 'double(10,3)');
    }
    
    /** @test */
    public function foreign_id_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->foreignId('user_id');
        });
        
        $builder->seeColumnOfType('user_id', 'bigint unsigned');
    }
    
    /** @test */
    public function geometry_collection_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->geometryCollection('positions');
        });
        
        $builder->seeColumnOfType('positions', 'geomcollection');
    }
    
    /** @test */
    public function id_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id('ID');
        });
        
        $builder->seeColumnOfType('ID', 'bigint unsigned');
        $builder->seePrimaryKey('ID');
    }
    
    /** @test */
    public function increments_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->increments('id');
        });
        
        $builder->seeColumnOfType('id', 'int unsigned');
        $builder->seePrimaryKey('id');
    }
    
    /** @test */
    public function integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->integer('amount');
        });
        
        $builder->seeColumnOfType('amount', 'int');
    }
    
    /** @test */
    public function ip_address_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->ipAddress('visitor');
        });
        
        $builder->seeColumnOfType('visitor', 'varchar(45)');
    }
    
    /** @test */
    public function json_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->json('options');
        });
        
        $builder->seeColumnOfType('options', 'json');
    }
    
    /** @test */
    public function json_b_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->jsonB('options');
        });
        
        $builder->seeColumnOfType('options', 'json');
    }
    
    /** @test */
    public function line_string_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->lineString('position');
        });
        
        $builder->seeColumnOfType('position', 'linestring');
    }
    
    /** @test */
    public function long_text_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->longText('description');
        });
        
        $builder->seeColumnOfType('description', 'longtext');
    }
    
    /** @test */
    public function mac_address_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->macAddress('device');
        });
        
        $builder->seeColumnOfType('device', 'varchar(17)');
    }
    
    /** @test */
    public function medium_increments_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->mediumIncrements('id');
        });
        
        $builder->seeColumnOfType('id', 'mediumint unsigned');
        $builder->seePrimaryKey('id');
    }
    
    /** @test */
    public function medium_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->mediumInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'mediumint');
    }
    
    /** @test */
    public function medium_text_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->mediumText('descriptions');
        });
        
        $builder->seeColumnOfType('descriptions', 'mediumtext');
    }
    
    /** @test */
    public function morphs_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->morphs('taggable');
        });
        
        $builder->seeColumnOfType('taggable_id', 'bigint unsigned');
        $builder->seeColumnOfType('taggable_type', 'varchar(255)');
    }
    
    /** @test */
    public function multi_line_string_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->multiLineString('positions');
        });
        
        $builder->seeColumnOfType('positions', 'multilinestring');
    }
    
    /** @test */
    public function multi_point_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->multiPoint('positions');
        });
        
        $builder->seeColumnOfType('positions', 'multipoint');
    }
    
    /** @test */
    public function multi_polygon_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->multiPolygon('positions');
        });
        
        $builder->seeColumnOfType('positions', 'multipolygon');
    }
    
    /** @test */
    public function nullable_timestamps_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->nullableTimestamps('1');
        });
        
        $builder->seeColumnOfType('created_at', 'timestamp(1)');
        $builder->seeColumnOfType('updated_at', 'timestamp(1)');
        $this->assertTrue($builder->seeNullableColumn('created_at'));
        $this->assertTrue($builder->seeNullableColumn('updated_at'));
    }
    
    /** @test */
    public function nullable_morphs_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->nullableMorphs('taggable');
        });
        
        $builder->seeColumnOfType('taggable_id', 'bigint unsigned');
        $builder->seeColumnOfType('taggable_type', 'varchar(255)');
        $this->assertTrue($builder->seeNullableColumn('taggable_id'));
        $this->assertTrue($builder->seeNullableColumn('taggable_type'));
    }
    
    /** @test */
    public function nullable_uuid_morphs_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->nullableUuidMorphs('taggable');
        });
        
        $builder->seeColumnOfType('taggable_id', 'char(36)');
        $builder->seeColumnOfType('taggable_type', 'varchar(255)');
        $this->assertTrue($builder->seeNullableColumn('taggable_id'));
        $this->assertTrue($builder->seeNullableColumn('taggable_type'));
    }
    
    /** @test */
    public function point_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->point('position');
        });
        
        $builder->seeColumnOfType('position', 'point');
    }
    
    /** @test */
    public function polygon_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->polygon('position');
        });
        
        $builder->seeColumnOfType('position', 'polygon');
    }
    
    /** @test */
    public function remember_token_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->rememberToken();
        });
        
        $builder->seeColumnOfType('remember_token', 'varchar(100)');
    }
    
    /** @test */
    public function set_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->set('flavors', ['strawberry', 'vanilla']);
        });
        
        $builder->seeColumnOfType('flavors', "set('strawberry','vanilla')");
    }
    
    /** @test */
    public function small_increments_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->smallIncrements('id');
        });
        
        $builder->seeColumnOfType('id', 'smallint unsigned');
        
        $builder->seePrimaryKey('id');
    }
    
    /** @test */
    public function small_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->smallInteger('amount');
        });
        
        $builder->seeColumnOfType('amount', 'smallint');
    }
    
    /** @test */
    public function soft_deletes_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->softDeletesTz('deleted_at');
            $table->softDeletesTz('deleted_at_precise', 2);
        });
        
        $builder->seeColumnOfType('deleted_at', 'timestamp');
        $builder->seeColumnOfType('deleted_at_precise', 'timestamp(2)');
    }
    
    /** @test */
    public function soft_deletes_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->softDeletes('deleted_at');
            $table->softDeletes('deleted_at_precise', 2);
        });
        
        $builder->seeColumnOfType('deleted_at', 'timestamp');
        $builder->seeColumnOfType('deleted_at_precise', 'timestamp(2)');
    }
    
    /** @test */
    public function string_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->string('name', 55);
        });
        
        $builder->seeColumnOfType('name', 'varchar(55)');
    }
    
    /** @test */
    public function text_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->text('description');
        });
        
        $builder->seeColumnOfType('description', 'text');
    }
    
    /** @test */
    public function time_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->timeTz('sunrise', 2);
        });
        
        $builder->seeColumnOfType('sunrise', 'time(2)');
    }
    
    /** @test */
    public function time_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->time('sunrise', 2);
        });
        
        $builder->seeColumnOfType('sunrise', 'time(2)');
    }
    
    /** @test */
    public function timestamp_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->timestampTz('added_at', 2);
        });
        
        $builder->seeColumnOfType('added_at', 'timestamp(2)');
    }
    
    /** @test */
    public function timestamp_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->timestamp('added_at', 2);
        });
        
        $builder->seeColumnOfType('added_at', 'timestamp(2)');
    }
    
    /** @test */
    public function timestamps_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->timestampsTz(2);
        });
        
        $builder->seeColumnOfType('created_at', 'timestamp(2)');
        $builder->seeColumnOfType('updated_at', 'timestamp(2)');
    }
    
    /** @test */
    public function timestamps_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->timestamps(2);
        });
        
        $builder->seeColumnOfType('created_at', 'timestamp(2)');
        $builder->seeColumnOfType('updated_at', 'timestamp(2)');
    }
    
    /** @test */
    public function tiny_increments_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->tinyIncrements('id');
        });
        
        $builder->seeColumnOfType('id', 'tinyint unsigned');
        
        $builder->seePrimaryKey('id');
    }
    
    /** @test */
    public function tiny_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->tinyInteger('amount');
        });
        
        $builder->seeColumnOfType('amount', 'tinyint');
    }
    
    /** @test */
    public function unsigned_big_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedBigInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'bigint unsigned');
    }
    
    /** @test */
    public function unsigned_decimal_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedDecimal('votes', '10', '2');
        });
        
        $builder->seeColumnOfType('votes', 'decimal(10,2) unsigned');
    }
    
    /** @test */
    public function unsigned_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'int unsigned');
    }
    
    /** @test */
    public function unsigned_medium_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedMediumInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'mediumint unsigned');
    }
    
    /** @test */
    public function unsigned_small_integer_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedSmallInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'smallint unsigned');
    }
    
    /** @test */
    public function unsigned_tiny_int_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->unsignedTinyInteger('votes');
        });
        
        $builder->seeColumnOfType('votes', 'tinyint unsigned');
    }
    
    /** @test */
    public function uuid_morphs_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->uuidMorphs('taggable');
        });
        
        $builder->seeColumnOfType('taggable_id', 'char(36)');
        $builder->seeColumnOfType('taggable_type', 'varchar(255)');
    }
    
    /** @test */
    public function uuid_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->uuid('id');
        });
        
        $builder->seeColumnOfType('id', 'char(36)');
    }
    
    /** @test */
    public function year_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->year('birth_year');
        });
        
        $builder->seeColumnOfType('birth_year', 'year');
    }
    
    /** @test */
    public function new_columns_can_be_inserted_after_existing_columns()
    {
        $this->builder->create('books', function (Blueprint $table) {
            $table->string('first_name');
            $table->string('email');
        });
        
        // With after() method
        $this->builder->table('books', function (Blueprint $table) {
            $table->string('last_name')->after('first_name');
            $table->string('phone')->after('last_name');
        });
        
        $this->assertSame(
            ['first_name', 'last_name', 'phone', 'email'],
            $this->getColumnsByOrdinalPosition('books')
        );
        
        $this->builder->table('books', function (Blueprint $table) {
            $table->after('phone', function ($table) {
                $table->string('address_line1');
                $table->string('city');
            });
        });
        
        $this->assertSame(
            ['first_name', 'last_name', 'phone', 'address_line1', 'city', 'email'],
            $this->getColumnsByOrdinalPosition('books')
        );
    }
    
    /** @test */
    public function auto_incrementing_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->integer('user_id')->autoIncrement();
            $table->string('email');
        });
        
        $this->wpdbInsert('wp_books', ['email' => 'calvin@gmail.com']);
        $this->wpdbInsert('wp_books', [
            'user_id' => 10,
            'email' => 'calvin@gmx.com',
        ]);
        $this->wpdbInsert('wp_books', ['email' => 'calvin@web.com']);
        
        $assert = new AssertableWpDB('wp_books');
        $assert->assertRecordExists(
            [
                'user_id' => 1,
                'email' => 'calvin@gmail.com',
            ]
        );
        $assert->assertRecordExists(
            [
                'user_id' => 10,
                'email' => 'calvin@gmx.com',
            ]
        );
        $assert->assertRecordExists(
            [
                'user_id' => 11,
                'email' => 'calvin@web.com',
            ]
        );
    }
    
    /** @test */
    public function charset_can_be_set_for_table_and_column()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->id();
            $table->string('name')->charset('latin1')->collation('latin1_german1_ci');
        });
        
        $this->assertSame('utf8mb4', $builder->getTableCharset('books'));
        
        $columns = $builder->getFullColumnInfo('books');
        
        $this->assertSame('latin1', Str::before($columns['name']->Collation, '_'));
    }
    
    /**
     * TEST FOR MODIFYING COLUMNS
     */
    
    /** @test */
    public function collation_can_be_set_for_table_and_column()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_520_ci';
            $table->id();
            $table->string('name')->collation('latin1_german1_ci');
        });
        
        $this->assertSame('utf8mb4_unicode_520_ci', $builder->getTableCollation('books'));
        
        $columns = $builder->getFullColumnInfo('books');
        
        $this->assertSame('latin1_german1_ci', $columns['name']->Collation);
    }
    
    /** @test */
    public function comments_can_be_added()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('My comment');
        });
        
        $name_col = $builder->getFullColumnInfo('books')['name'];
        
        $this->assertSame('My comment', $name_col->Comment);
    }
    
    /** @test */
    public function a_default_value_can_be_set()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('count')->default(10);
            $table->string('name')->default('calvin alkan');
            $table->json('movies')->default(new Expression('(JSON_ARRAY())'));
        });
        
        $this->wpdbInsert('wp_books', ['id' => 1]);
        
        $expected = [
            'id' => '1',
            'count' => '10',
            'name' => 'calvin alkan',
            'movies' => '[]',
        ];
        
        $this->assertDbTable()->assertRecordEquals(['id' => 1], $expected);
    }
    
    /** @test */
    public function a_column_can_be_added_at_the_first_place()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('count');
            $table->string('name');
        });
        
        $this->assertSame([
            'id',
            'count',
            'name',
        ], $builder->getColumnsByOrdinalPosition('books'));
        
        $builder->table('books', function (Blueprint $table) {
            $table->string('email')->first();
        });
        
        $this->assertSame(
            ['email', 'id', 'count', 'name'],
            $builder->getColumnsByOrdinalPosition('books')
        );
    }
    
    /** @test */
    public function a_column_can_be_nullable()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable(false);
        });
        
        try {
            $this->wpdbInsert('wp_books', ['id' => 1]);
            $this->fail('Non-nullable column was created without default value');
        } catch (mysqli_sql_exception $e) {
            $this->assertSame(
                "Field 'email' doesn't have a default value",
                $e->getMessage()
            );
        }
        
        $builder->dropColumns('books', 'email');
        
        $builder->table('books', function (Blueprint $table) {
            $table->string('email')->nullable(true);
        });
        
        $this->wpdbInsert('wp_books', ['id' => 1]);
        
        $this->assertTrue(true);
    }
    
    /** @test */
    public function a_stored_column_can_be_created()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->storedAs("CONCAT(first_name,' ',last_name)");
            $table->integer('price');
            $table->integer('discounted_price')->storedAs('price -5')->unsigned();
        });
        
        $this->wpdbInsert('wp_books',
            [
                'id' => 1,
                'first_name' => 'calvin',
                'last_name' => 'alkan',
                'price' => 10,
            ]
        );
        
        $expected = [
            'id' => '1',
            'first_name' => 'calvin',
            'last_name' => 'alkan',
            'full_name' => 'calvin alkan',
            'price' => '10',
            'discounted_price' => '5',
        ];
        
        $assert = new AssertableWpDB('wp_books');
        $assert->assertRecordEquals([
            'id' => 1,
            'first_name' => 'calvin',
            'last_name' => 'alkan',
            'price' => 10,
        ], $expected);
    }
    
    /** @test */
    public function a_virtual_column_can_be_created()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('full_name')->virtualAs("CONCAT(first_name,' ',last_name)");
            $table->integer('price');
            $table->integer('discounted_price')->virtualAs('price -5')->unsigned();
        });
        
        $this->wpdbInsert('wp_books',
            [
                'id' => 1,
                'first_name' => 'calvin',
                'last_name' => 'alkan',
                'price' => 10,
            ]
        );
        
        $expected = [
            'id' => '1',
            'first_name' => 'calvin',
            'last_name' => 'alkan',
            'full_name' => 'calvin alkan',
            'price' => '10',
            'discounted_price' => '5',
        ];
        
        $assert = new AssertableWpDB('wp_books');
        $assert->assertRecordEquals([
            'id' => 1,
            'first_name' => 'calvin',
            'last_name' => 'alkan',
            'price' => 10,
        ], $expected);
    }
    
    /** @test */
    public function integers_can_be_unsigned()
    {
        $builder = $this->newTestBuilder('books');
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->integer('price')->unsigned();
        });
        
        $this->wpdbInsert('wp_books', ['id' => 1, 'price' => 10]);
        
        $expected = ['id' => '1', 'price' => '10'];
        
        $this->assertDbTable()->assertRecordEquals(['id' => 1, 'price' => 10], $expected);
        
        try {
            global $wpdb;
            
            $wpdb->query("INSERT INTO `wp_books` (`id`, `price`) VALUES (2, -100)");
            
            $this->fail('[TEST FAILED] Negative value inserted for unsigned integer.');
        } catch (mysqli_sql_exception $e) {
            $this->assertSame(
                "Out of range value for column 'price' at row 1",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function timestamps_can_use_the_current_time_as_default()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->timestamp('time')->nullable();
            $table->timestamp('time_non_nullable')->useCurrent();
        });
        
        $this->wpdbInsert('wp_books', ['id' => 1]);
        
        global $wpdb;
        
        $row = $wpdb->get_row("select * from `wp_books` where `id` = '1'", ARRAY_N);
        
        $this->assertSame('1', $row[0]);
        $this->assertNull($row[1]);
        $this->assertNotNull($row[2]);
    }
    
    /** @test */
    public function test_drop_morphs_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->morphs('taggable');
        });
        
        $this->assertSame(
            ['id', 'taggable_type', 'taggable_id'],
            $builder->getColumnsByOrdinalPosition('books')
        );
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropMorphs('taggable');
        });
        
        $this->assertSame(['id'], $builder->getColumnsByOrdinalPosition('books'));
    }
    
    /** @test */
    public function test_remember_token_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->rememberToken();
        });
        
        $this->assertSame([
            'id',
            'remember_token',
        ], $builder->getColumnsByOrdinalPosition('books'));
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropRememberToken();
        });
        
        $this->assertSame(['id'], $builder->getColumnsByOrdinalPosition('books'));
    }
    
    /** @test */
    public function test_drop_soft_deletes_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        
        $this->assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('books'));
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        $this->assertSame(['id'], $builder->getColumnListing('books'));
    }
    
    /**
     * TESTS FOR DROPPING COLUMNS WITH ALIASES
     */
    
    /** @test */
    public function test_drop_soft_deletes_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->softDeletesTz();
        });
        
        $this->assertSame(['id', 'deleted_at'], $builder->getColumnsByOrdinalPosition('books'));
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropSoftDeletesTz();
        });
        
        $this->assertSame(['id'], $builder->getColumnListing('books'));
    }
    
    /** @test */
    public function test_drop_timestamps_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        
        $this->assertSame(
            ['id', 'created_at', 'updated_at'],
            $builder->getColumnsByOrdinalPosition('books')
        );
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropTimestamps();
        });
        
        $this->assertSame(['id'], $builder->getColumnListing('books'));
    }
    
    /** @test */
    public function test_drop_timestamps_tz_works()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->timestampsTz();
        });
        
        $this->assertSame(
            ['id', 'created_at', 'updated_at'],
            $builder->getColumnsByOrdinalPosition('books')
        );
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropTimestampsTz();
        });
        
        $this->assertSame(['id'], $builder->getColumnListing('books'));
    }
    
    /** @test */
    public function unique_indexes_work()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
        });
        
        $builder->seeUniqueColumn('email');
        
        $builder->table('books', function (Blueprint $table) {
            $table->unique('name');
        });
        
        $builder->seeUniqueColumn('email');
        $builder->seeUniqueColumn('name');
    }
    
    /** @test */
    public function normal_indexes_work()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('name');
            $table->string('address');
        });
        
        $builder->seeIndexColumn('email');
        
        $builder->table('books', function (Blueprint $table) {
            $table->index('address');
        });
        
        $builder->seeIndexColumn('address');
    }
    
    /** @test */
    public function a_composite_index_can_be_added()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('address');
        });
        
        $builder->table('books', function (Blueprint $table) {
            $table->index(['name', 'email', 'address']);
        });
        
        $builder->seeIndexColumn('name');
    }
    
    
    /**
     * Creating indexes
     */
    
    /** @test */
    public function a_primary_key_index_can_be_created()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->string('email');
            $table->string('name')->primary();
        });
        
        $builder->seePrimaryKey('name');
    }
    
    /** @test */
    public function an_index_can_be_renamed()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
        });
        
        $builder->seeIndexColumn('name');
        
        $builder->table('books', function (Blueprint $table) {
            $table->renameIndex('books_name_index', 'new_index');
        });
        
        $builder->seeIndexColumn('name');
    }
    
    /** @test */
    public function indexes_can_be_dropped()
    {
        $builder = $this->newTestBuilder('books');
        
        $builder->create('books', function (Blueprint $table) {
            $table->integer('amount')->primary();
            $table->string('name')->index();
            $table->string('phone')->index();
            $table->string('email')->unique();
        });
        
        $builder->seeIndexColumn('name');
        $builder->seePrimaryKey('amount');
        $builder->seeUniqueColumn('email');
        $builder->seeIndexColumn('phone');
        
        $builder->table('books', function (Blueprint $table) {
            $table->dropPrimary('books_amount_primary');
            $table->dropUnique('books_email_unique');
            $table->dropIndex(['phone']);
            $table->dropIndex(['name']);
        });
        
        $name = $builder->getFullColumnInfo('books')['amount'];
        $this->assertEmpty($name->Key);
        
        $name = $builder->getFullColumnInfo('books')['name'];
        $this->assertEmpty($name->Key);
        
        $email = $builder->getFullColumnInfo('books')['email'];
        $this->assertEmpty($email->Key);
        
        $phone = $builder->getFullColumnInfo('books')['phone'];
        $this->assertEmpty($phone->Key);
    }
    
    /** @test */
    public function foreign_keys_can_be_created()
    {
        $builder1 = $this->newTestBuilder('authors');
        
        $builder1->create('authors', function (Blueprint $table) {
            $table->id();
        });
        
        $builder2 = $this->newTestBuilder('books');
        
        $builder2->create('books', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('author_id')->unique()
                  ->constrained()
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
        
        $builder1->seePrimaryKey('id');
        $builder2->seePrimaryKey('id');
    }
    
    /** @test */
    public function foreign_keys_cascade_correctly_on_update()
    {
        $builder1 = $this->newTestBuilder('authors');
        
        $builder1->create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
        });
        
        $builder2 = $this->newTestBuilder('books');
        
        $builder2->create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->unique()
                  ->constrained()
                  ->onUpdate('cascade');
        });
        
        $this->wpdbInsert('wp_authors', ['author_name' => 'calvin alkan']);
        $this->wpdbInsert('wp_books', ['id' => 1, 'author_id' => 1]);
        
        $this->wpdbUpdate('wp_authors', ['id' => 2], ['author_name' => 'calvin alkan']);
        
        $this->assertDbTable()->assertRecordExists(['id' => 1, 'author_id' => 2]);
    }
    
    /**
     * Dropping Indexes.
     */
    
    /** @test */
    public function foreign_keys_cascade_correctly_on_delete()
    {
        $builder1 = $this->newTestBuilder('authors');
        
        $builder1->create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
        });
        
        $builder2 = $this->newTestBuilder('books');
        
        $builder2->create('books', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('author_id')->unique()
                  ->constrained()
                  ->onDelete('cascade');
        });
        
        $this->wpdbInsert('wp_authors', ['author_name' => 'calvin alkan']);
        $this->wpdbInsert('wp_books', ['id' => 1, 'author_id' => 1]);
        
        $this->assertDbTable('wp_books')->assertRecordExists(['id' => 1, 'author_id' => 1]);
        
        $this->wpdbDelete('wp_authors', ['id' => 1]);
        
        $this->assertDbTable('wp_books')->assertRecordNotExists(['id' => 1, 'author_id' => 1]);
    }
    
    /** @test */
    public function foreign_keys_can_be_dropped()
    {
        $builder1 = $this->newTestBuilder('authors');
        
        $builder1->create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
        });
        
        $builder2 = $this->newTestBuilder('books');
        
        $builder2->create('books', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('author_id')->unique()
                  ->constrained();
        });
        
        $this->wpdbInsert('wp_authors', ['author_name' => 'calvin alkan']);
        $this->wpdbInsert('wp_books', ['id' => 1, 'author_id' => 1]);
        
        $builder2->table('books', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
        });
        
        $this->wpdbDelete('wp_authors', ['id' => 1]);
        
        // Our record is still here because we dropped the foreign key. Otherwise this would blow up
        $this->assertDbTable('wp_books')->assertRecordExists(['id' => 1, 'author_id' => 1]);
    }
    
    /**
     * Foreign Key Constraints
     */
    
    protected function assertDbTable(string $table_name = 'wp_books') :AssertableWpDB
    {
        return new AssertableWpDB($table_name);
    }
    
    private function newTestBuilder(string $table) :TestSchemaBuilder
    {
        return new TestSchemaBuilder($this->mysqli_connection, $table);
    }
    
    private function getColumnsByOrdinalPosition(string $table_name) :array
    {
        global $wpdb;
        $table_name = "$wpdb->prefix".trim($table_name, $wpdb->prefix);
        
        $columns = collect($this->getFullColumnInfo($table_name));
        
        return $columns->pluck('Field')->toArray();
    }
    
    private function getFullColumnInfo(string $table_with_prefix)
    {
        global $wpdb;
        
        return $wpdb->get_results("show full columns from $table_with_prefix", ARRAY_A);
    }
    
}

/**
 * Class TestSchemaBuilder
 *
 * @see MySqlBuilder
 */
class TestSchemaBuilder extends MySqlBuilder
{
    
    /**
     * @var string|null
     */
    private $table;
    
    public function __construct($connection, $table = null)
    {
        $this->table = $table;
        
        parent::__construct($connection);
    }
    
    public function seeColumnOfType($column, $type)
    {
        $table = $this->table;
        
        PHPUnit::assertTrue(
            $this->hasColumn($table, $column),
            'Column: '.$column.' not found.'
        );
        PHPUnit::assertSame(
            $type,
            $this->getColumnType($table, $column),
            'Column types dont match for column: '.$column
        );
    }
    
    public function seePrimaryKey($column)
    {
        $col = $this->getFullColumnInfo($this->table)[$column];
        PHPUnit::assertTrue($col->Key === 'PRI');
    }
    
    public function seeNullableColumn(string $column) :bool
    {
        $col = $this->getFullColumnInfo($this->table)[$column];
        
        return $col->Null === 'YES';
    }
    
    public function seeUniqueColumn(string $column)
    {
        $col = $this->getFullColumnInfo($this->table)[$column];
        PHPUnit::assertTrue($col->Key === 'UNI');
    }
    
    public function seeIndexColumn(string $column)
    {
        $col = $this->getFullColumnInfo($this->table)[$column];
        PHPUnit::assertTrue($col->Key === 'MUL');
    }
    
    public function getAllTables() :array
    {
        $parent = collect(parent::getAllTables());
        
        $key = 'Tables_in_'.$this->connection->getDatabaseName();
        
        return $parent->pluck($key)->toArray();
    }
    
    public function getColumnsByOrdinalPosition($table)
    {
        $query = "show full columns from ".$this->connection->getTablePrefix().$table;
        
        $col_info = collect($this->connection->select($query));
        
        return $col_info->pluck('Field')->toArray();
    }
    
    public function getTableCharset($table) :string
    {
        $collation = $this->getTableCollation($table);
        
        return Str::before($collation, '_');
    }
    
    public function getTableCollation($table)
    {
        $query = "show table status where name like ?";
        
        $bindings = [$this->connection->getTablePrefix().$table];
        $results = $this->connection->select($query, $bindings);
        return $results[0]->Collation;
    }
    
    /**
     * Get the data type for the given column name.
     *
     * @param  string  $table
     * @param  string  $column
     *
     * @return string
     */
    public function getColumnType($table, $column) :string
    {
        return $this->getFullColumnInfo($table)[$column]->Type ?? '';
    }
    
    public function getFullColumnInfo($table) :array
    {
        $query = "show full columns from ?";
        
        $binding = $this->connection->getTablePrefix().$table;
        
        $col_info = collect(
            $this->connection->select(
                str_replace('?', $binding, $query)
            )
        );
        
        $field_names = $col_info->pluck('Field');
        
        return $field_names->combine($col_info)->toArray();
    }
    
}


