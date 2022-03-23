<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade;

use Exception;
use Illuminate\Support\Str;
use Illuminate\View\Factory as IlluminateViewFactory;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\Exception\ViewNotFound;
use Snicco\Component\Templating\ValueObject\FilePath;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewFactory\ViewFactory;
use Throwable;

use function array_map;
use function rtrim;
use function strtr;

use const DIRECTORY_SEPARATOR;

final class BladeViewFactory implements ViewFactory
{
    private IlluminateViewFactory $view_factory;

    /**
     * @var string[]
     */
    private array $view_directories_with_trailing_separator;

    /**
     * @param string[] $view_directories
     */
    public function __construct(IlluminateViewFactory $view_factory, array $view_directories)
    {
        $this->view_factory = $view_factory;
        $this->view_directories_with_trailing_separator = array_map(
            fn(string $dir): string => rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            $view_directories
        );
    }

    /**
     * @interal
     *
     * @throws ViewNotFound
     *
     * @psalm-internal Snicco\Bridge\Blade
     */
    public function make(string $view): View
    {
        $name = $this->normalizeName($view);
        try {
            $path = $this->view_factory->getFinder()->find($name);

            return new View($view, FilePath::fromString($path), self::class);
        } catch (Exception $e) {
            throw ViewNotFound::forView($view, $e);
        }
    }

    /**
     * @interal
     *
     * @throws ViewCantBeRendered
     *
     * @psalm-internal Snicco\Bridge\Blade
     */
    public function toString(View $view): string
    {
        $name = $this->normalizeName($view->name());
        try {
            return $this->view_factory->make($name, $view->context())->render();
        } catch (Throwable $e) {
            throw ViewCantBeRendered::fromPrevious($view->name(), $e);
        }
    }

    private function normalizeName(string $name): string
    {
        // Blade only supports views that are relative to a view directory and that don't contain any path endings.
        $replacements = [];
        foreach ($this->view_directories_with_trailing_separator as $view_directory) {
            $replacements[$view_directory] = '';
        }

        $name = strtr($name, $replacements);

        $name = Str::beforeLast($name, '.blade.php');

        if (Str::endsWith($name, '.php')) {
            $name = Str::beforeLast($name, '.php');
        }

        return $name;
    }
}
