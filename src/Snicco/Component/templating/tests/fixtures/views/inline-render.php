<?php

declare(strict_types=1);

use Snicco\Component\Templating\TemplateEngine;

/**
 * @var TemplateEngine $view
 */
echo 'foo:inline=>' . ($view->render('greeting', [
    'name' => $name ?? 'Calvin',
]));
