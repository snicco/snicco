<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

class City extends TestWPModel
{
    public function country(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }
}
