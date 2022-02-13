<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Renderer;

use RuntimeException;
use Snicco\Component\HttpRouting\Exception\CouldNotRenderTemplate;
use Throwable;
use Webmozart\Assert\Assert;

use function extract;
use function ob_get_clean;
use function ob_start;
use function sprintf;

use const EXTR_SKIP;

final class FileTemplateRenderer implements TemplateRenderer
{

    /**
     * @psalm-suppress UnresolvableInclude
     */
    public function render(string $template_name, array $data = []): string
    {
        try {
            $this->validateTemplate($template_name);

            ob_start();
            (static function () use ($template_name, $data) {
                extract($data, EXTR_SKIP);
                require $template_name;
            })();
            $output = ob_get_clean();
            if (false === $output) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not get output buffer contents.');
                // @codeCoverageIgnoreEnd
            }
            return $output;
        } catch (Throwable $e) {
            throw CouldNotRenderTemplate::fromPrevious($e);
        }
    }

    private function validateTemplate(string $template_name): void
    {
        Assert::file(
            $template_name,
            sprintf(
                "[%s] only supports an absolute filepath for %s.\n[%s] is not a file.",
                self::class,
                '$template_name',
                $template_name
            )
        );
    }

}