<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface ViewComposer
{
    
    public function compose(ViewInterface $view);
    
}