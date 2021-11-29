<?php

declare(strict_types=1);

namespace Tests\fixtures\database\Models;

class Activity extends TestWPModel
{
    
    public function cities()
    {
        return $this->belongsToMany(City::class);
    }
    
}