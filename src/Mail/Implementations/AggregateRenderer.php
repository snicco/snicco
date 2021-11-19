<?php

declare(strict_types=1);

namespace Snicco\Mail\Implementations;

use Snicco\Mail\Contracts\MailRenderer;
use Snicco\Mail\Exceptions\MailRenderingException;

/**
 * @api
 */
final class AggregateRenderer implements MailRenderer
{
    
    /**
     * @var MailRenderer
     */
    private $renderers;
    
    /**
     * @var array<string,MailRenderer>
     */
    private $renderer_cache;
    
    public function __construct(MailRenderer ...$renderers)
    {
        $this->renderers = $renderers;
    }
    
    /**
     * @param  string  $view
     * @param  array  $context
     * @param  bool  $plain_text
     *
     * @return string
     * @throws MailRenderingException
     */
    public function getMailContent(string $view, array $context = [], bool $plain_text = false) :string
    {
        if (isset($this->renderer_cache[$view])) {
            return $this->renderer_cache[$view]->getMailContent($view, $context);
        }
        
        $renderer = null;
        $extension = pathinfo($view, PATHINFO_EXTENSION);
        $extension = empty($extension) ? null : $extension;
        foreach ($this->renderers as $r) {
            if ($r->supports($view, $extension)) {
                $renderer = $r;
                $this->renderer_cache[$view] = $r;
                break;
            }
        }
        
        if ( ! $renderer) {
            throw new MailRenderingException(
                "None of the given renderers supports the current the view [$view]."
            );
        }
        
        return $renderer->getMailContent($view, $context);
    }
    
    public function supports(string $view, ?string $extension = null) :bool
    {
        return true;
    }
    
}