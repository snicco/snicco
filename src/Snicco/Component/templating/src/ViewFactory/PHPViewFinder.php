<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewNotFound;

use function array_map;
use function rtrim;

use const DIRECTORY_SEPARATOR;

final class PHPViewFinder
{
    /**
     * @var list<string>
     */
    private array $directories;

    /**
     * @param list<string> $directories
     */
    public function __construct(array $directories = [])
    {
        $this->directories = $this->normalize($directories);
    }

    /**
     * @throws ViewNotFound
     *
     * @psalm-internal Snicco\Component\Templating
     */
    public function filePath(string $view_name): string
    {
        if (is_file($view_name)) {
            return $view_name;
        }

        $view_name = $this->normalizeViewName($view_name);

        foreach ($this->directories as $directory) {
            $path = rtrim($directory, '/') . '/' . $view_name . '.php';

            $exists = is_file($path);

            if ($exists) {
                return $path;
            }
        }

        throw new ViewNotFound("No file can be found for view name [{$view_name}].");
    }

    /**
     * @psalm-internal Snicco\Component\Templating
     */
    public function includeFile(string $path, array $context): void
    {
        (static function () use ($path, $context): void {
            extract($context, EXTR_SKIP);
            unset($context);
            /** @psalm-suppress UnresolvableInclude */
            require $path;
        })();
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private function normalize(array $directories): array
    {
        return array_map(fn (string $dir) => rtrim($dir, DIRECTORY_SEPARATOR), $directories);
    }

    private function normalizeViewName(string $view_name): string
    {
        $name = strstr($view_name, '.php', true);
        $name = (false === $name) ? $view_name : $name;

        $name = trim($name, '/');

        return str_replace('.', '/', $name);
    }
}
