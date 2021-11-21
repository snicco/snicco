<?php

declare(strict_types=1);

namespace Snicco\View;

use Throwable;
use Snicco\Support\Arr;
use Snicco\Events\MakingView;
use Snicco\Contracts\ViewEngine;
use Snicco\Contracts\ViewInterface;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\ExceptionHandling\Exceptions\ViewException;
use Snicco\ExceptionHandling\Exceptions\ViewNotFoundException;

class PhpViewEngine implements ViewEngine
{
    
    private PhpViewFinder $finder;
    private Dispatcher    $dispatcher;
    
    public function __construct(PhpViewFinder $finder, Dispatcher $dispatcher)
    {
        $this->finder = $finder;
        $this->dispatcher = $dispatcher;
    }
    
    public function make($views) :ViewInterface
    {
        $views = Arr::wrap($views);
        
        $view_name = collect($views)
            ->reject(fn(string $view_name) => ! $this->finder->exists($view_name))
            ->whenEmpty(function () use ($views) {
                throw new ViewNotFoundException(
                    'Views not found. Tried ['.implode(', ', $views).']'
                );
            })
            ->first();
        
        return new PhpView($this, $view_name, $this->finder->filePath($view_name));
    }
    
    public function renderPhpView(PhpView $view) :string
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
    
    private function render(PhpView $view)
    {
        if ($view->parent() !== null) {
            $shared = array_diff($view->context(), $view->parent()->context());
            
            $view->parent()
                 ->with(Arr::except($shared, '__content'))
                 ->with(
                     '__content',
                     new ChildContent(fn() => $this->requireView($view))
                 );
            
            $this->render($view->parent());
            
            return;
        }
        
        $this->requireView($view);
    }
    
    private function requireView(PhpView $view)
    {
        $this->dispatcher->dispatch(new MakingView($view));
        
        $this->finder->includeFile(
            $view->path(),
            $view->context()
        );
    }
    
    private function handleViewException(Throwable $e, $ob_level, PhpView $view)
    {
        while (ob_get_level() > $ob_level) {
            ob_end_clean();
        }
        
        throw new ViewException(
            'Error rendering view: ['.$view->name().'].', $e
        );
    }
    
}
