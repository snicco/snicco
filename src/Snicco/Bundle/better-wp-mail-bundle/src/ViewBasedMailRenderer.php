<?php

declare(strict_types=1);

namespace Snicco\MailBundle;

use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Component\Templating\ViewEngine;
use Snicco\Mail\Exceptions\MailRenderingException;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;

/**
 * @interal
 */
final class ViewBasedMailRenderer implements MailRenderer
{
    
    private ViewEngine $view_engine;
    
    public function __construct(ViewEngine $view_engine)
    {
        $this->view_engine = $view_engine;
    }
    
    public function getMailContent(string $template_name, array $context = []) :string
    {
        try {
            $view = $this->view_engine->make($template_name);
        } catch (ViewNotFound $not_found_exception) {
            throw new MailRenderingException(
                "The mail template [$template_name] does not exist: Caused by: {$not_found_exception->getMessage()}",
                $not_found_exception->getCode(),
                $not_found_exception
            );
        }
        
        try {
            $view->with($context);
            return $view->toString();
        } catch (ViewCantBeRendered $e) {
            throw new MailRenderingException(
                "The mail template [$template_name] could not be rendered: Caused by: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        try {
            return $this->view_engine->make($view) instanceof View;
        } catch (ViewNotFound $e) {
            return false;
        }
    }
    
}