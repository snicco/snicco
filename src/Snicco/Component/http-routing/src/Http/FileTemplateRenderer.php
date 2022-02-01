<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use Webmozart\Assert\Assert;

use function sprintf;

final class FileTemplateRenderer implements TemplateRenderer
{

    public function render(string $template_name, array $data = []): string
    {
        $this->validateTemplate($template_name);

        ob_start();
        (static function () use ($template_name, $data) {
            extract($data, EXTR_SKIP);
            require $template_name;
        })();
        return ob_get_clean();
    }

    private function validateTemplate(string $template_name): void
    {
        Assert::file(
            $template_name,
            sprintf(
                "[%s] only supports an absolute filepath for %s.\n[%s] is not file.",
                self::class,
                '$template_name',
                $template_name
            )
        );
    }

}