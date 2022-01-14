<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use RuntimeException;
use Snicco\Support\Str;
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
        
        if (isset($this->context[$key])) {
            return $this->context[$key];
        }
        
        return $default;
    }
    
    /**
     * Create a view instance for the given view's layout header, if any.
     *
     * @return ViewInterface|PHPView|null
     */
    private function parseParentView() :?PHPView
    {
        $parent_view_name = $this->parseExtends();
        
        if (null === $parent_view_name) {
            return null;
        }
        
        return $this->engine->make([$parent_view_name]);
    }
    
    private function parseExtends() :?string
    {
        $data = file_get_contents($this->filepath, false, null, 0, 100);
        
        if (false === $data) {
            throw new RuntimeException("Cant read file contents of view [$this->filepath].");
        }
        
        $scope = Str::betweenFirst($data, '/*', '*/');
        
        $match = preg_match('/(?:Extends:\s?)(.+)/', $scope, $matches);
        
        if (false === $match) {
            throw new RuntimeException("preg_match failed on string [$scope]");
        }
        if (0 === $match) {
            return null;
        }
        
        if ( ! isset($matches[1])) {
            return null;
        }
        
        $match = str_replace(' ', '', $matches[1]);
        
        if ('' === $match) {
            return null;
        }
        
        return $match;
    }
    
}
