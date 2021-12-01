<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\Renderer\AggregateRenderer;
use Snicco\Mail\Exceptions\MailRenderingException;

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