<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Contracts;

use Snicco\Component\BetterWPMail\Renderer\AggregateRenderer;
use Snicco\Component\BetterWPMail\Exceptions\MailRenderingException;

/**
 * @api
 */
interface MailRenderer
{
    
    /**
     * @param  string  $template_name
     * @param  array  $context
     *
     * @return string|resource
     * @throws MailRenderingException
     */
    public function getMailContent(string $template_name, array $context = []) :string;
    
    /**
     * @param  string  $view
     * @param  string|null  $extension
     *
     * @return bool
     * @see AggregateRenderer
     */
    public function supports(string $view, ?string $extension = null) :bool;
    
}