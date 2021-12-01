<?php

declare(strict_types=1);

namespace Tests\Database\fixtures\Factories;

use Tests\Database\fixtures\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

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