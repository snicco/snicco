<?php


    declare(strict_types = 1);


    namespace Tests\integration\Database;

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    trait WithTestTables
    {

        private $tables = [
            'cities',
            'countries',
            'activities',
            'activity_city',
        ];

        protected static $tables_created = false;

        protected function withNewTables()
        {

            if ( ! static::$tables_created) {

                $this->dropTables();
                $this->createTables();

            }

            static::$tables_created = true;

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