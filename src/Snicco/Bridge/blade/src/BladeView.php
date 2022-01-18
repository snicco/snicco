<?php

declare(strict_types=1);

namespace Snicco\Blade;

use Throwable;
use Snicco\Component\Templating\View\View;
use Illuminate\Contracts\View\View as IlluminateViewContract;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;

/**
 * @internal
 */
final class BladeView implements View, IlluminateViewContract
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
            throw new ViewCantBeRendered(
                "Error rendering view:[{$this->name()}]\nCaused by: {$e->getMessage()}",
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
    public function with($key, $value = null) :View
    {
        $this->illuminate_view->with($key, $value);
        return $this;
    }
    
    public function context(string $key = null, $default = null)
    {
        $data = $this->illuminate_view->getData();
        
        if ($key === null) {
            return $data;
        }
        
        if (isset($data[$key])) {
            return $data[$key];
        }
        
        return $default;
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