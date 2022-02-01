<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Snicco\Component\Eloquent\Tests\fixtures\Factory\CountryFactory;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WithTestTables;
use Snicco\Component\Eloquent\Tests\fixtures\Helper\WPDBTestHelpers;
use Snicco\Component\Eloquent\Tests\fixtures\Model\Activity;
use Snicco\Component\Eloquent\Tests\fixtures\Model\City;
use Snicco\Component\Eloquent\Tests\fixtures\Model\Country;
use Snicco\Component\Eloquent\WPEloquentStandalone;

class DatabaseFactoriesTest extends WPTestCase
{

    use WPDBTestHelpers;
    use WithTestTables;

    /**
     * @test
     */
    public function a_factory_can_be_created(): void
    {
        $factory = Country::factory();

        $this->assertInstanceOf(CountryFactory::class, $factory);
    }

    /**
     * @test
     */
    public function a_factory_can_create_a_model(): void
    {
        $country = Country::factory()->make();

        $this->assertInstanceOf(Country::class, $country);
    }

    /**
     * @test
     */
    public function testFactoryStates(): void
    {
        $country = Country::factory()->narnia()->make();

        $this->assertInstanceOf(Country::class, $country);
        $this->assertSame('Narnia', $country->continent);
    }

    /**
     * @test
     */
    public function testFactoryMultiple(): void
    {
        $country = Country::factory()->count(3)->make();

        $this->assertInstanceOf(Collection::class, $country);
        $this->assertCount(3, $country);
        $country->each(function ($country) {
            $this->assertInstanceOf(Country::class, $country);
        });
    }

    /**
     * @test
     */
    public function testWithAttributeOverride(): void
    {
        $country = Country::factory()->make([
            'name' => 'My country',
        ]);

        $this->assertInstanceOf(Country::class, $country);
        $this->assertSame('My country', $country->name);
    }

    /**
     * @test
     */
    public function testMakeDoesNotPersistModel(): void
    {
        $country = Country::factory()->make();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordNotExists(['name' => $country->name]);
        $table->assertTotalCount(0);
    }

    /**
     * @test
     */
    public function testCreatePersistsModel(): void
    {
        $country = Country::factory()->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $country->name]);
    }

    /**
     * @test
     */
    public function testCreateManyPersistsMany(): void
    {
        $countries = Country::factory()->count(3)->create();

        $table = $this->assertDbTable('wp_countries');

        $countries->each(function ($country) use ($table) {
            $table->assertRecordExists(['name' => $country->name]);
        });

        $table->assertTotalCount(3);
    }

    /**
     * @test
     */
    public function testCreateWithAttributeOverride(): void
    {
        $country = Country::factory()->create([
            'continent' => 'Narnia',
        ]);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $country->name, 'continent' => 'Narnia']);
    }

    /**
     * @test
     */
    public function testWithSequence(): void
    {
        $countries = Country::factory()
            ->count(6)
            ->state(
                new Sequence(
                    ['continent' => 'Narnia'],
                    ['continent' => 'Westeros'],
                )
            )
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertTotalCount(6);

        $countries_in_narnia = $countries->filter(function ($country) {
            return $country->continent === 'Narnia';
        });

        $countries_in_westeros = $countries->filter(function ($country) {
            return $country->continent === 'Westeros';
        });

        $this->assertCount(3, $countries_in_narnia);
        $this->assertCount(3, $countries_in_westeros);
    }

    /**
     * @test
     */
    public function testHasMany(): void
    {
        $country = Country::factory()
            ->has(City::factory()->count(3))
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['id' => $country->id]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $country->id], 3);
    }

    /**
     * @test
     */
    public function testHasManyWithMagicMethod(): void
    {
        $country = Country::factory()
            ->hasCities(3)
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['id' => $country->id]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $country->id], 3);
    }

    /**
     * @test
     */
    public function testHasManyWithCustomAttributes(): void
    {
        $country = Country::factory()
            ->hasCities(3, function (array $attributes, Country $country) {
                return ['population' => 10];
            })
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['id' => $country->id]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $country->id, 'population' => 10], 3);
    }

    /**
     * @test
     */
    public function testBelongsTo(): void
    {
        $cities = City::factory()
            ->count(3)
            ->for(
                Country::factory()->state([
                    'name' => 'Narnia',
                ])
            )
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $cities[0]->country->name]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $cities[0]->country->id], 3);
    }

    /**
     * @test
     */
    public function testBelongsToWithMagicMethod(): void
    {
        $cities = City::factory()
            ->count(3)
            ->forCountry([
                'name' => 'Narnia',
            ])
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $cities[0]->country->name]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $cities[0]->country->id], 3);
    }

    /**
     * @test
     */
    public function testManyToMany(): void
    {
        $city = City::factory()
            ->has(Activity::factory()->count(3))
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $city->country->name]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $city->country->id], 1);

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere(['city_id' => $city->id], 3);
    }

    /**
     * @test
     */
    public function testManyToManyWithAttached(): void
    {
        $city = City::factory()
            ->hasAttached(
                Activity::factory()->count(3),
                ['popularity' => 10]
            )
            ->create();

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere(['city_id' => $city->id, 'popularity' => 10], 3);
    }

    /**
     * @test
     */
    public function testHasAttachedWithExisting(): void
    {
        $countries = Activity::factory()
            ->count(4)
            ->create();

        $city = City::factory()
            ->hasAttached($countries)
            ->create();

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(4);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere(['city_id' => $city->id], 4);
    }

    /**
     * @test
     */
    public function testManyToManyWithMagicMethod(): void
    {
        $city = City::factory()
            ->hasActivities(3)
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists(['name' => $city->country->name]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere(['country_id' => $city->country->id], 1);

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere(['city_id' => $city->id], 3);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Model::unsetEventDispatcher();
        Model::unsetConnectionResolver();

        ($eloquent = new WPEloquentStandalone())->bootstrap();
        $eloquent->withDatabaseFactories(
            'Snicco\\Component\\Eloquent\\Tests\\fixtures\\Model',
            'Snicco\\Component\\Eloquent\\Tests\\fixtures\\Factory',
        );
        $this->withNewTables();
        DB::table('countries')->delete();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

}



