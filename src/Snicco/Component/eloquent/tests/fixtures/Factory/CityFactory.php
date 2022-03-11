<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Snicco\Component\Eloquent\Tests\fixtures\Model\Country;

class CityFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->city(),
            'country_id' => Country::factory(),
            'population' => $this->faker->numberBetween(100000, 1000000),
        ];
    }
}
