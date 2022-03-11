<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Renderer;

use Snicco\Component\BetterWPMail\Exception\CouldNotRenderMailContent;

final class AggregateRenderer implements MailRenderer
{
    /**
     * @var MailRenderer[]
     */
    private array $renderers;

    /**
     * @var array<string,MailRenderer>
     */
    private $renderer_cache = [];

    public function __construct(MailRenderer ...$renderers)
    {
        $this->renderers = $renderers;
    }

    /**
     * @param array<string,mixed> $context
     *
     * @throws CouldNotRenderMailContent
     */
    public function render(string $template_name, array $context = []): string
    {
        if (isset($this->renderer_cache[$template_name])) {
            return $this->renderer_cache[$template_name]->render($template_name, $context);
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

        if (! $renderer) {
            throw new CouldNotRenderMailContent(
                "None of the given renderers supports the current the template [{$template_name}]."
            );
        }

        return $renderer->render($template_name, $context);
    }

    public function supports(string $template_name, ?string $extension = null): bool
    {
        return true;
    }
}
