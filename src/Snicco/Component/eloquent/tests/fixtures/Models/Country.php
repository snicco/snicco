<?php

declare(strict_types=1);

namespace Tests\Database\fixtures\Models;

class Country extends TestWPModel
{
    
    protected $dispatchesEvents = [
        'created' => CountryCreated::class,
    ];
    
    public function cities()
    {
        return $this->hasMany(City::class);
    }
    
}