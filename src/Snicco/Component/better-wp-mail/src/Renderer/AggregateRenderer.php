<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Renderer;

use Snicco\Component\BetterWPMail\Contracts\MailRenderer;
use Snicco\Component\BetterWPMail\Exceptions\MailRenderingException;

/**
 * @api
 */
final class AggregateRenderer implements MailRenderer
{
    
    /**
     * @var MailRenderer[]
     */
    private array $renderers;
    
    /**
     * @var array<string,MailRenderer>
     */
    private $renderer_cache;
    
    public function __construct(MailRenderer ...$renderers)
    {
        $this->renderers = $renderers;
    }
    
    /**
     * @param  string  $template_name
     * @param  array  $context
     *
     * @return string|resource
     * @throws MailRenderingException
     */
    public function getMailContent(string $template_name, array $context = []) :string
    {
        if (isset($this->renderer_cache[$template_name])) {
            return $this->renderer_cache[$template_name]->getMailContent($template_name, $context);
        }
        
        $renderer = null;
        $extension = pathinfo($template_name, PATHINFO_EXTENSION);
        $extension = empty($extension) ? null : $extension;
        foreach ($this->renderers as $r) {
            if ($r->supports($template_name, $extension)) {
                $renderer = $r;
                $this->renderer_cache[$template_name] = $r;
                break;
            }
        }
        
        if ( ! $renderer) {
            throw new MailRenderingException(
                "None of the given renderers supports the current the view [$template_name]."
            );
        }
        
        return $renderer->getMailContent($template_name, $context);
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        return true;
    }
    
}