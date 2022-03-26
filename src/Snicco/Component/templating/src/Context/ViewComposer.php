<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Context;

use Snicco\Component\Templating\ValueObject\View;

interface ViewComposer
{
    public function compose(View $view): View;
}
