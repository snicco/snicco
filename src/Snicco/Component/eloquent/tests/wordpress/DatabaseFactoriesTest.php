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

/**
 * @internal
 *
 * @psalm-suppress MixedArgumentTypeCoercion
 * @psalm-suppress UndefinedMagicPropertyFetch
 */
final class DatabaseFactoriesTest extends WPTestCase
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

        ($eloquent = new WPEloquentStandalone())
            ->bootstrap();
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
    public function test_factory_states(): void
    {
        $country = Country::factory()->narnia()->make();

        $this->assertInstanceOf(Country::class, $country);
        $this->assertSame('Narnia', $country->continent);
    }

    /**
     * @test
     */
    public function test_factory_multiple(): void
    {
        $country = Country::factory()->count(3)->make();

        $this->assertInstanceOf(Collection::class, $country);
        $this->assertCount(3, $country);
        $country->each(function ($country): void {
            $this->assertInstanceOf(Country::class, $country);
        });
    }

    /**
     * @test
     */
    public function test_with_attribute_override(): void
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
    public function test_make_does_not_persist_model(): void
    {
        $country = Country::factory()->make();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordNotExists([
            'name' => (string) $country->name,
        ]);
        $table->assertTotalCount(0);
    }

    /**
     * @test
     */
    public function test_create_persists_model(): void
    {
        $country = Country::factory()->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => (string) $country->name,
        ]);
    }

    /**
     * @test
     */
    public function test_create_many_persists_many(): void
    {
        $countries = Country::factory()->count(3)->create();

        $table = $this->assertDbTable('wp_countries');

        $countries->each(function (Country $country) use ($table): void {
            $table->assertRecordExists([
                'name' => (string) $country->name,
            ]);
        });

        $table->assertTotalCount(3);
    }

    /**
     * @test
     */
    public function test_create_with_attribute_override(): void
    {
        $country = Country::factory()->create([
            'continent' => 'Narnia',
        ]);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => (string) $country->name,
            'continent' => 'Narnia',
        ]);
    }

    /**
     * @test
     */
    public function test_with_sequence(): void
    {
        $countries = Country::factory()
            ->count(6)
            ->state(new Sequence([
                'continent' => 'Narnia',
            ], [
                'continent' => 'Westeros',
            ], ))
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertTotalCount(6);

        $countries_in_narnia = $countries->filter(
            fn (Country $country): bool => 'Narnia' === (string) $country->continent
        );

        $countries_in_westeros = $countries->filter(
            fn (Country $country): bool => 'Westeros' === (string) $country->continent
        );

        $this->assertCount(3, $countries_in_narnia);
        $this->assertCount(3, $countries_in_westeros);
    }

    /**
     * @test
     */
    public function test_has_many(): void
    {
        $country = Country::factory()
            ->has(City::factory()->count(3))
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'id' => (int) $country->id,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => $country->id,
        ], 3);
    }

    /**
     * @test
     */
    public function test_has_many_with_magic_method(): void
    {
        $country = Country::factory()
            ->hasCities(3)
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'id' => (int) $country->id,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => (int) $country->id,
        ], 3);
    }

    /**
     * @test
     */
    public function test_has_many_with_custom_attributes(): void
    {
        $country = Country::factory()
            ->hasCities(3, fn (array $attributes, Country $country): array => [
                'population' => 10,
            ])
            ->create();

        $this->assertInstanceOf(Country::class, $country);

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'id' => (int) $country->id,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => (int) $country->id,
            'population' => 10,
        ], 3);
    }

    /**
     * @test
     */
    public function test_belongs_to(): void
    {
        $cities = City::factory()
            ->count(3)
            ->for(Country::factory()->state([
                'name' => 'Narnia',
            ]))
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => (string) $cities[0]->country->name,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => (string) $cities[0]->country->id,
        ], 3);
    }

    /**
     * @test
     */
    public function test_belongs_to_with_magic_method(): void
    {
        $cities = City::factory()
            ->count(3)
            ->forCountry([
                'name' => 'Narnia',
            ])
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => $cities[0]->country->name,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => $cities[0]->country->id,
        ], 3);
    }

    /**
     * @test
     */
    public function test_many_to_many(): void
    {
        $city = City::factory()
            ->has(Activity::factory()->count(3))
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => (string) $city->country->name,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => (string) $city->country->id,
        ], 1);

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere([
            'city_id' => (int) $city->id,
        ], 3);
    }

    /**
     * @test
     */
    public function test_many_to_many_with_attached(): void
    {
        $city = City::factory()
            ->hasAttached(Activity::factory()->count(3), [
                'popularity' => 10,
            ])
            ->create();

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere([
            'city_id' => (int) $city->id,
            'popularity' => 10,
        ], 3);
    }

    /**
     * @test
     */
    public function test_has_attached_with_existing(): void
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
        $table->assertCountWhere([
            'city_id' => (int) $city->id,
        ], 4);
    }

    /**
     * @test
     */
    public function test_many_to_many_with_magic_method(): void
    {
        $city = City::factory()
            ->hasActivities(3)
            ->create();

        $table = $this->assertDbTable('wp_countries');
        $table->assertRecordExists([
            'name' => (string) $city->country->name,
        ]);
        $table->assertTotalCount(1);

        $table = $this->assertDbTable('wp_cities');
        $table->assertCountWhere([
            'country_id' => (int) $city->country->id,
        ], 1);

        $table = $this->assertDbTable('wp_activities');
        $table->assertTotalCount(3);

        $table = $this->assertDbTable('wp_activity_city');
        $table->assertCountWhere([
            'city_id' => (int) $city->id,
        ], 3);
    }
}
