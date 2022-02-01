<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Snicco\Component\StrArr\Arr;

class CountryFactory extends Factory
{

    public function definition()
    {
        return [
            'name' => $this->faker->country(),
            'continent' => $this->continent(),
        ];
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

    /**
     * @return static
     */
    public function narnia(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'continent' => 'Narnia',
            ];
        });
    }

}