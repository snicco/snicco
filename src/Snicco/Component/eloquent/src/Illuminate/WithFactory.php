<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Illuminate;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory as WithIlluminateFactory;

/**
 * @api
 */
trait WithFactory
{
    
    use WithIlluminateFactory;
    
    protected static function newFactory() :Factory
    {
        $model = Str::afterLast(static::class, '\\');
        $factory = $model.'Factory';
        $factory = trim(static::$factory_namespace, "\\").'\\'.$factory;
        return new $factory();
    }
    
}