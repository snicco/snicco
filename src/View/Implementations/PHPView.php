<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use Snicco\Support\WP;
use Snicco\Support\Arr;
use Snicco\View\Contracts\ViewInterface;

/**
 * @api
 */
class PHPView implements ViewInterface
{
    
    /**
     * Name of view file header based on which to resolve parent views.
     *
     * @var string
     */
    public const PARENT_FILE_INDICATOR = 'Extends';
    
    /**
     * @var PHPViewFactory
     */
    private $engine;
    
    /**
     * @var string
     */
    private $filepath;
    
    /**
     * @var PHPView|null
     */
    private $parent_view;
    
    /**
     * @var array
     */
    private $context = [];
    
    /**
     * @var string
     */
    private $name;
    
    public function __construct(PHPViewFactory $engine, string $name, string $path)
    {
        $this->engine = $engine;
        $this->name = $name;
        $this->filepath = $path;
        $this->parent_view = $this->parseParentView();
    }
    
    public function path() :string
    {
        return $this->filepath;
    }
    
    public function parent() :?PHPView
    {
        return $this->parent_view;
    }
    
    public function name() :string
    {
        return $this->name;
    }
    
    public function toResponsable() :string
    {
        return $this->toString();
    }
    
    public function toString() :string
    {
        return $this->engine->renderPhpView($this);
    }
    
    /**
     * @param  string|array<string, mixed>  $key
     * @param  mixed  $value
     *
     * @return static                      $this
     */
    public function with($key, $value = null) :ViewInterface
    {
        if (is_array($key)) {
            $this->context = array_merge($this->context(), $key);
        }
        else {
            $this->context[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * @param  string|null  $key
     * @param  mixed|null  $default
     *
     * @return mixed
     */
    public function context(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->context;
        }
        
        return Arr::get($this->context, $key, $default);
    }
    
    /**
     * Create a view instance for the given view's layout header, if any.
     *
     * @return ViewInterface|PHPView|null
     */
    private function parseParentView() :?PHPView
    {
        if (empty($file_headers = $this->parseFileHeaders())) {
            return null;
        }
        
        $parent_view_name = trim($file_headers[0]);
        
        return $this->engine->make([$parent_view_name]);
    }
    
    private function parseFileHeaders() :array
    {
        return array_filter(
            WP::fileHeaderData(
                $this->filepath,
                [self::PARENT_FILE_INDICATOR]
            )
        );
    }
    
}
