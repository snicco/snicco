<?php

declare(strict_types=1);

namespace Snicco\View;

use Closure;
use Snicco\Support\Arr;
use Illuminate\Support\Collection;
use Snicco\Contracts\ViewInterface;
use Snicco\Factories\ViewComposerFactory;

class ViewComposerCollection
{
    
    private Collection $composers;
    private ViewComposerFactory $composer_factory;
    
    public function __construct(ViewComposerFactory $composer_factory)
    {
        $this->composers = new Collection();
        $this->composer_factory = $composer_factory;
    }
    
    /**
     * @param  string|string[]  $views
     * @param  string|Closure class name or closure
     */
    public function addComposer($views, $composer)
    {
        $this->composers->push([
            
            'views' => Arr::wrap($views),
            'composer' => $composer,
        
        ]);
    }
    
    public function compose(ViewInterface $view)
    {
        $composers = $this->matchingComposers($view);
        
        array_walk(
            $composers,
            fn($composer) => $this->composer_factory->create($composer)->compose($view)
        );
    }
    
    private function matchingComposers(ViewInterface $view) :array
    {
        return $this->composers
            ->filter(function ($value) use ($view) {
                return in_array($view->name(), $value['views']);
            })
            ->pluck('composer')
            ->all();
    }
    
}