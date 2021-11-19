<?php

declare(strict_types=1);

namespace Snicco\Mail\Contracts;

use Snicco\Mail\Exceptions\MailRenderingException;
use Snicco\Mail\Implementations\AggregateRenderer;

/**
 * @api
 */
interface MailRenderer
{
    
    /**
     * @param  string  $view
     * @param  array  $context
     *
     * @return string
     * @throws MailRenderingException
     */
    public function getMailContent(string $view, array $context = []) :string;
    
    /**
     * @param  string  $view
     * @param  string|null  $extension
     *
     * @return bool
     * @see AggregateRenderer
     */
    public function supports(string $view, ?string $extension = null) :bool;
    
}