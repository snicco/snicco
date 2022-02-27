<?php

declare(strict_types=1);


namespace Snicco\Bundle\BetterWPMail;

use Snicco\Component\BetterWPMail\Exception\CouldNotRenderMailContent;
use Snicco\Component\BetterWPMail\Renderer\MailRenderer;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ViewEngine;

final class ViewEngineMailRenderer implements MailRenderer
{

    private ViewEngine $view_engine;

    /**
     * @var array<string,bool>
     */
    private array $views = [];

    public function __construct(ViewEngine $view_engine)
    {
        $this->view_engine = $view_engine;
    }

    public function render(string $template_name, array $context = []): string
    {
        try {
            return $this->view_engine->render($template_name, $context);
        } catch (ViewCantBeRendered $e) {
            throw new CouldNotRenderMailContent(
                $e->getMessage(), 0, $e
            );
        }
    }

    public function supports(string $template_name, ?string $extension = null): bool
    {
        if (isset($this->views[$template_name])) {
            return $this->views[$template_name];
        }

        try {
            $this->view_engine->make($template_name);
            $this->views[$template_name] = true;
            return true;
        } catch (ViewNotFound $e) {
            $this->views[$template_name] = false;
        }
        return false;
    }
}