<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Contracts\ViewInterface;
use Snicco\Core\Events\EventObjects\CoreEvent;

class MakingView extends CoreEvent
{
    
    public ViewInterface $view;
    
    public function __construct(ViewInterface $view)
    {
        $this->view = $view;
    }
    
}