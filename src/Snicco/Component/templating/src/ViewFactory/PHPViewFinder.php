<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\Exception\InvalidFile;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ValueObject\FilePath;

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
        $this->directories = $this->normalizeDirectories($directories);
    }

    /**
     * @internal
     *
     * @throws ViewNotFound
     *
     * @psalm-internal Snicco\Component\Templating
     */
    public function filePath(string $view_name): FilePath
    {
        try {
            return FilePath::fromString($view_name);
        } catch (InvalidFile $e) {
            //
        }

        $view_name = $this->normalizeViewName($view_name);

        foreach ($this->directories as $directory) {
            $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $view_name . '.php';

            try {
                return FilePath::fromString($path);
            } catch (InvalidFile $e) {
                //
            }
        }

        throw new ViewNotFound(sprintf('No file can be found for view name [%s].', $view_name));
    }

    /**
     * @internal
     *
     * @psalm-internal Snicco\Component\Templating
     */
    public function includeFile(FilePath $path, array $context): void
    {
        (static function () use ($path, $context): void {
            extract($context, EXTR_SKIP);
            unset($context);
            /** @psalm-suppress UnresolvableInclude */
            require (string)$path;
        })();
    }

    /**
     * @param list<string> $directories
     *
     * @return list<string>
     */
    private function normalizeDirectories(array $directories): array
    {
        return array_map(fn(string $dir): string => rtrim($dir, DIRECTORY_SEPARATOR), $directories);
    }

    private function normalizeViewName(string $view_name): string
    {
        $view_name = Str::beforeFirst($view_name, '.php');
        $view_name = trim($view_name, DIRECTORY_SEPARATOR);

        return Str::replaceAll($view_name, '.', DIRECTORY_SEPARATOR);
    }
}
