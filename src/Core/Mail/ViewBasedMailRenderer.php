<?php

declare(strict_types=1);

namespace Snicco\Core\Mail;

use Snicco\Mail\Contracts\MailRenderer;

final class ViewBasedMailRenderer implements MailRenderer
{
    
    public function getMailContent(string $template_name, array $context = []) :string
    {
        // TODO: Implement getMailContent() method.
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        // TODO: Implement supports() method.
    }
    
}