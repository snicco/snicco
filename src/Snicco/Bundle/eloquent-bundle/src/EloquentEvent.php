<?php

declare(strict_types=1);

namespace Snicco\EloquentBundle;

use Illuminate\Database\Eloquent\Model;
use Snicco\Component\BetterWPHooks\Mutable;
use Snicco\Component\EventDispatcher\Event;

class EloquentEvent implements Event, Mutable
{
    
    /**
     * @var Model
     */
    private $model;
    
    /**
     * @var string|null
     */
    private $name;
    
    public function __construct(Model $model, string $name = null)
    {
        $this->model = $model;
        $this->name = $name;
    }
    
    public function payload()
    {
        return $this->model;
    }
    
    public function name() :string
    {
        return $this->name ?? static::class;
    }
    
}