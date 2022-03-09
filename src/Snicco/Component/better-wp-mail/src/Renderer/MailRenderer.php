<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPMail\Renderer;

use Snicco\Component\BetterWPMail\Exception\CouldNotRenderMailContent;

interface MailRenderer
{

    /**
     * @param array<string,mixed> $context
     *
     * @throws CouldNotRenderMailContent
     */
    public function render(string $template_name, array $context = []): string;

    /**
     * @see AggregateRenderer
     */
    public function supports(string $template_name, ?string $extension = null): bool;

}