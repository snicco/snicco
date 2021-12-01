<?php

declare(strict_types=1);

namespace Snicco\View\Implementations;

use Throwable;
use Snicco\Support\Arr;
use Snicco\View\ChildContent;
use Snicco\View\Contracts\ViewFactory;
use Snicco\View\ViewComposerCollection;
use Snicco\View\Contracts\ViewInterface;
use Snicco\View\Exceptions\ViewNotFoundException;
use Snicco\View\Exceptions\ViewRenderingException;

final class PHPViewFactory implements ViewFactory
{
    
    /**
     * @var PHPViewFinder
     */
    private $finder;
    
    /**
     * @var ViewComposerCollection
     */
    private $composer_collection;
    
    public function __construct(PHPViewFinder $finder, ViewComposerCollection $composers)
    {
        $this->finder = $finder;
        $this->composer_collection = $composers;
    }
    
    public function make(array $views) :ViewInterface
    {
        $first_matching_view = null;
        
        foreach ($views as $view) {
            if ($this->finder->exists($view)) {
                $first_matching_view = $view;
                break;
            }
        }
        
        if ( ! $first_matching_view) {
            throw new ViewNotFoundException(
                sprintf("Non of the provided views exists. Tried: [%s]", implode(', ', $views))
            );
        }
        
        return new PHPView(
            $this,
            $first_matching_view,
            $this->finder->filePath($first_matching_view)
        );
    }
    
    /**
     * @interal
     */
    public function renderPhpView(PHPView $view) :string
    {
        $ob_level = ob_get_level();
        
        ob_start();
        
        try {
            $this->render($view);
        } catch (Throwable $e) {
            $this->handleViewException($e, $ob_level, $view);
        }
        
        return ltrim(ob_get_clean());
    }
    
    private function render(PHPView $view)
    {
        $this->composer_collection->compose($view);
        
        if ($view->parent() !== null) {
            $view->parent()
                 ->with(Arr::except($view->context(), '__content'))
                 ->with(
                     '__content',
                     new ChildContent(function () use ($view) {
                         $this->requireView($view);
                     })
                 );
            
            $this->render($view->parent());
            
            return;
        }
        
        $this->requireView($view);
    }
    
    private function requireView(PHPView $view)
    {
        $this->finder->includeFile(
            $view->path(),
            $view->context()
        );
    }
    
    private function handleViewException(Throwable $e, $ob_level, PHPView $view)
    {
        while (ob_get_level() > $ob_level) {
            ob_end_clean();
        }
        
        throw new ViewRenderingException(
            "Error rendering view: [{$view->name()}].\nCaused by: {$e->getMessage()}",
            $e->getCode(),
            $e
        );
    }
    
}
