<?php

declare(strict_types=1);

namespace Snicco\Contracts;

interface PhpEngine extends ViewEngineInterface
{
    
    public function renderPhpView(PhpViewInterface $view) :string;
    
}