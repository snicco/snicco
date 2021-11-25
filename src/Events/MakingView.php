<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\View\Contracts\ViewInterface;
use Snicco\EventDispatcher\Contracts\Mutable;
use Snicco\Core\Events\EventObjects\CoreEvent;

class MakingView extends CoreEvent implements Mutable
{
    
    public ViewInterface $view;
    
    public function __construct(ViewInterface $view)
    {
        $this->view = $view;
    }
    
}