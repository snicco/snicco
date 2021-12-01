<?php

declare(strict_types=1);

namespace Tests\Database\fixtures\Factories;

use Snicco\Support\Arr;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    
    public function definition()
    {
        return [
            'name' => $this->faker->country(),
            'continent' => $this->continent(),
        ];
    }
    
    public function narnia()
    {
        return $this->state(function (array $attributes) {
            return [
                'continent' => 'Narnia',
            ];
        });
    }
    
    private function continent()
    {
        $continents = [
            'Asia',
            'Africa',
            'Europe',
            'Australia',
            'North America',
            'South America',
            'Antarctica.',
        ];
        
        return Arr::random($continents);
    }
    
}