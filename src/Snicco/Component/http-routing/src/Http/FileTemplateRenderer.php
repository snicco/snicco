<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http;

use RuntimeException;
use Webmozart\Assert\Assert;

use function sprintf;

final class FileTemplateRenderer implements TemplateRenderer
{

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function render(string $template_name, array $data = []): string
    {
        $this->validateTemplate($template_name);

        ob_start();
        (static function () use ($template_name, $data) {
            extract($data, EXTR_SKIP);
            require $template_name;
        })();
        $output = ob_get_clean();
        if (false === $output) {
            throw new RuntimeException('Could not get output buffer contents.');
        }
        return $output;
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