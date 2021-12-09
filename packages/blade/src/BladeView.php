<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Throwable;
use Snicco\Support\Arr;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewRenderingException;
use Illuminate\Contracts\View\View as IlluminateViewContract;

/**
 * @internal
 */
final class BladeView implements ViewInterface, IlluminateViewContract
{
    
    /**
     * @var IlluminateViewContract
     */
    private $illuminate_view;
    
    public function __construct($illuminate_view)
    {
        $this->illuminate_view = $illuminate_view;
    }
    
    public function toString() :string
    {
        try {
            return $this->illuminate_view->render();
        } catch (Throwable $e) {
            throw new ViewRenderingException(
                "Error rendering view:[{$this->name()}] Caused by: {$e->getMessage()}",
                $e->getCode(),
                $e,
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
    
    public function path() :string
    {
        return $this->illuminate_view->getPath();
    }
    
}