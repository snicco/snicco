<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewNotFound;

use function rtrim;

use const DIRECTORY_SEPARATOR;

/**
 * @interal
 */
final class PHPViewFinder
{

    /**
     * Directories in which we search for views.
     *
     * @param string[] $directories
     */
    private array $directories;

    public function __construct(array $directories = [])
    {
        $this->directories = $this->normalize($directories);
    }

    private function normalize(array $directories): array
    {
        return array_filter(
            array_map(fn(string $dir) => rtrim($dir, DIRECTORY_SEPARATOR), $directories)
        );
    }

    /**
     * @throws ViewNotFound
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
        throw new ViewNotFound("No file can be found for view name [$view_name].");
    }

    private function normalizeViewName(string $view_name)
    {
        $name = strstr($view_name, '.php', true);
        $name = ($name === false) ? $view_name : $name;

        $name = trim($name, '/');
        return str_replace('.', '/', $name);
    }

    public function includeFile(string $path, array $context)
    {
        return (static function () use ($path, $context) {
            extract($context, EXTR_SKIP);
            unset($context);
            return require $path;
        })();
    }

}
