<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Snicco\Component\StrArr\Arr;

final class ActivityFactory extends Factory
{
    /**
     * @return array{name: string}
     */
    public function definition(): array
    {
        return [
            'name' => $this->activity(),
        ];
    }

    private function activity(): string
    {
        $activities = ['football', 'tenis', 'basketball', 'swimming', 'sightseeing', 'hockey'];

        return Arr::random($activities)[0];
    }
}
