<?php

declare(strict_types=1);

namespace Tests\Database\helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait WithTestTables
{
    
    protected static $tables_created = false;
    
    /**
     * @var array
     */
    private $tables = [
        'cities',
        'countries',
        'activities',
        'activity_city',
    ];
    
    protected function withNewTables()
    {
        if ( ! static::$tables_created) {
            $this->dropTables();
            $this->createTables();
            $this->insertInitialRecords();
        }
        
        static::$tables_created = true;
    }
    
    protected function insertInitialRecords()
    {
        DB::table('countries')->insert([
            ['name' => 'germany', 'continent' => 'europe'],
            ['name' => 'spain', 'continent' => 'europe'],
            ['name' => 'france', 'continent' => 'europe'],
            ['name' => 'united kingdom', 'continent' => 'europe'],
            ['name' => 'mexico', 'continent' => 'north america'],
            ['name' => 'canada', 'continent' => 'north america'],
            ['name' => 'us', 'continent' => 'north america'],
        ]);
        DB::table('cities')->insert([
            ['name' => 'berlin', 'country_id' => 1, 'population' => 3],
            ['name' => 'munich', 'country_id' => 1, 'population' => 2],
            ['name' => 'madrid', 'country_id' => 2, 'population' => 3],
            ['name' => 'barcelona', 'country_id' => 2, 'population' => 2],
            ['name' => 'paris', 'country_id' => 3, 'population' => 4],
            ['name' => 'london', 'country_id' => 4, 'population' => 4],
            ['name' => 'mexico city', 'country_id' => 5, 'population' => 10],
            ['name' => 'vancouver', 'country_id' => 6, 'population' => 4],
            ['name' => 'new york', 'country_id' => 7, 'population' => 8],
            ['name' => 'los angeles', 'country_id' => 7, 'population' => 6],
        ]);
    }
    
    private function dropTables()
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
    }
    
    private function createTables()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('continent');
            $table->timestamps();
        });
        
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')
                  ->constrained()
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->integer('population');
            $table->timestamps();
        });
        
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('activity_city', function (Blueprint $table) {
            $table->integer('city_id');
            $table->integer('activity_id');
            $table->integer('popularity')->nullable();
        });
    }
    
}