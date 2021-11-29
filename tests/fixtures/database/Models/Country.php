<?php

declare(strict_types=1);

namespace Tests\fixtures\database\Models;

class Country extends TestWPModel
{
    
    public function cities()
    {
        return $this->hasMany(City::class);
    }
    
}