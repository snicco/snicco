<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Renderer;

use Snicco\Component\BetterWPMail\Exception\CantRenderMailContent;

/**
 * @api
 */
interface MailRenderer
{
    
    /**
     * @throws CantRenderMailContent
     */
    public function getMailContent(string $template_name, array $context = []) :string;
    
    /**
     * @see AggregateRenderer
     */
    public function supports(string $template_name, ?string $extension = null) :bool;
    
}