<?php

declare(strict_types=1);

namespace Snicco\EloquentBundle;

use Illuminate\Database\Eloquent\Model;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\Mutable;

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
    
    public function getPayload()
    {
        return $this->model;
    }
    
    public function getName() :string
    {
        return $this->name ?? static::class;
    }
    
}