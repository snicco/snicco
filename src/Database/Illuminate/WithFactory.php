<?php

declare(strict_types=1);

namespace Snicco\Database\Illuminate;

use Snicco\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory as WithEloquentFactory;

trait WithFactory
{
    
    use WithEloquentFactory;
    
    protected static function newFactory() :Factory
    {
        $model = Str::afterLast(static::class, '\\');
        $factory = $model.'Factory';
        $factory = trim(static::$factory_namespace, "\\").'\\'.$factory;
        return new $factory();
    }
    
}