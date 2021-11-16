<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\Contracts\ViewInterface;
use Snicco\Contracts\ViewFactoryInterface;

class ComposeView
{
    
    private ViewFactoryInterface $view_factory;
    
    public function __construct(ViewFactoryInterface $view_factory)
    {
        $this->view_factory = $view_factory;
    }
    
    public function __invoke(ViewInterface $view)
    {
        $this->view_factory->compose($view);
    }
    
}