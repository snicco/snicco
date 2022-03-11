<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends TestWPModel
{
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class);
    }
}
