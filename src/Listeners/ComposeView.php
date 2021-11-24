<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\Events\MakingView;
use Snicco\View\Contracts\ViewFactoryInterface;

class ComposeView
{
    
    private ViewFactoryInterface $view_factory;
    
    public function __construct(ViewFactoryInterface $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function handle(MakingView $event)
    {
        $this->view_factory->compose($event->view);
    }
    
}