<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Illuminate;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory as WithIlluminateFactory;
use Illuminate\Support\Str;

trait WithFactory
{
    use WithIlluminateFactory;

    /**
     * @psalm-suppress InvalidStringClass
     * @psalm-suppress MoreSpecificReturnType
     */
    protected static function newFactory(): Factory
    {
        $model = Str::afterLast(static::class, '\\');
        $factory = $model . 'Factory';
        $factory = trim(static::$factory_namespace, "\\") . '\\' . $factory;
        return new $factory();
    }
}
