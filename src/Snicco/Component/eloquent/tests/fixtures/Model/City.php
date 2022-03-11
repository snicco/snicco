<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class City extends TestWPModel
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }
}
