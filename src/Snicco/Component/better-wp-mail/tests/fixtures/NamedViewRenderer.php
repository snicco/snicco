<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Tests\fixtures;

use Snicco\Component\BetterWPMail\Contracts\MailRenderer;

class NamedViewRenderer implements MailRenderer
{
    
    public function getMailContent(string $template_name, array $context = []) :string
    {
        return $template_name;
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        return $extension === 'php' || strpos($view, '.') !== false;
    }
    
}