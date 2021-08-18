<?php

declare(strict_types=1);

namespace Tests\fixtures\database\Models;

class Country extends TestModel
{
    
    public function cities()
    {
        return $this->hasMany(City::class);
    }
    
}