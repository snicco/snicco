<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

class Activity extends TestWPModel
{

    public function cities()
    {
        return $this->belongsToMany(City::class);
    }

}