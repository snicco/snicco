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

    /**
     * @return static
     */
    public function narnia(): self
    {
        return $this->state(fn (array $attributes) => [
            'continent' => 'Narnia',
        ]);
    }

    private function continent()
    {
        $continents = ['Asia', 'Africa', 'Europe', 'Australia', 'North America', 'South America', 'Antarctica.'];

        return Arr::random($continents)[0];
    }
}
