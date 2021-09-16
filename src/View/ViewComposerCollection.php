<?php

declare(strict_types=1);

namespace Snicco\View;

use Exception;
use Snicco\Support\Arr;
use Snicco\Support\Str;
use Snicco\Contracts\ViewComposer;
use Illuminate\Support\Collection;
use Snicco\Contracts\ViewInterface;
use Snicco\Factories\ViewComposerFactory;

class ViewComposerCollection implements ViewComposer
{
    
    private Collection $composers;
    
    private ViewComposerFactory $composer_factory;
    
    public function __construct(ViewComposerFactory $composer_factory)
    {
        
        $this->composers = new Collection();
        $this->composer_factory = $composer_factory;
        
    }
    
    public function executeUsing(...$args)
    {
        
        $view = $args[0];
        
        $composers = $this->matchingComposers($view);
        
        array_walk($composers, fn(ViewComposer $composer) => $composer->executeUsing($view));
        
    }
    
    /**
     * @param  string|string[]  $views
     * @param  string|array|callable  $callable
     *
     * @throws Exception
     */
    public function addComposer($views, $callable)
    {
        
        $this->composers->push([
            
            'views' => Arr::wrap($views),
            'composer' => $this->composer_factory->createUsing($callable),
        
        ]);
        
    }
    
    private function matchingComposers(ViewInterface $view) :array
    {
        
        return $this->composers
            ->filter(function ($value) use ($view) {
                
                // Needed since we reference views by file name only while blade keeps the "dot" nesting.
                $name = Str::afterLast($view->name(), '.');
                
                return in_array($name, $value['views']);
                
            }
            )
            ->pluck('composer')
            ->all();
        
    }
    
}