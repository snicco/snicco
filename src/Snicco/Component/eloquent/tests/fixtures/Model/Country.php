<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends TestWPModel
{

    /**
     * @var string[]
     */
    protected $dispatchesEvents = [
        'created' => CountryCreated::class,
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

}