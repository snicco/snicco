<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Throwable;
use Snicco\Support\Arr;
use Snicco\Events\MakingView;
use Snicco\Contracts\ViewInterface;
use Snicco\ExceptionHandling\Exceptions\ViewException;
use Illuminate\Contracts\View\View as IlluminateViewContract;

class BladeView implements ViewInterface, IlluminateViewContract
{
    
    private IlluminateViewContract $illuminate_view;
    
    public function __construct($illuminate_view)
    {
        $this->illuminate_view = $illuminate_view;
    }
    
    public function toResponsable() :string
    {
        
        return $this->toString();
    }
    
    public function toString() :string
    {
        
        try {
            
            MakingView::dispatch([$this]);
            
            return $this->illuminate_view->render();
            
        } catch (Throwable $e) {
            
            throw new ViewException(
                'Error rendering view:['.$this->name().']', $e
            );
            
        }
        
    }
    
    public function render() :string
    {
        
        return $this->toString();
    }
    
    public function name() :string
    {
        return $this->illuminate_view->name();
    }
    
    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     *
     * @return $this
     */
    public function with($key, $value = null) :ViewInterface
    {
        $this->illuminate_view->with($key, $value);
        return $this;
    }
    
    public function context(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->illuminate_view->getData();
        }
        
        return Arr::get($this->illuminate_view->getData(), $key, $default);
    }
    
    public function getData() :array
    {
        return $this->context();
    }
    
    public function path()
    {
        return $this->illuminate_view->getPath();
    }
    
}