<?php

declare(strict_types=1);

use Snicco\Component\Templating\ViewEngine;

/**
 * @var ViewEngine $view
 */
echo 'foo:inline=>' . ($view->render('greeting', [
    'name' => $name ?? 'Calvin',
]));
