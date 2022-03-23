<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use RuntimeException;
use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\OutputBuffer;
use Snicco\Component\Templating\ValueObject\View;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Throwable;

use function file_get_contents;
use function ltrim;
use function ob_get_level;
use function preg_match;
use function sprintf;
use function str_replace;

final class PHPViewFactory implements ViewFactory
{

    /**
     * Name of view file header based on which to resolve parent views.
     *
     * @var string
     */
    private const PARENT_FILE_INDICATOR = 'Extends';

    private PHPViewFinder $finder;

    private ViewComposerCollection $composer_collection;

    public function __construct(PHPViewFinder $finder, ViewComposerCollection $composers)
    {
        $this->finder = $finder;
        $this->composer_collection = $composers;
    }

    public function make(string $view): View
    {
        return new View($view, $this->finder->filePath($view), self::class);
    }

    public function toString(View $view): string
    {
        $ob_level = ob_get_level();

        OutputBuffer::start();

        try {
            $this->renderView($view);
        } catch (Throwable $e) {
            $this->handleViewException($e, $ob_level, $view);
        }

        return ltrim(OutputBuffer::get());
    }

    /**
     * @throws ViewCantBeRendered
     */
    private function renderView(View $view): void
    {
        $view = $this->composer_collection->compose($view);

        $parent_view_name = $this->parseParentName($view);

        if (null !== $parent_view_name) {
            $parent = $this->make($parent_view_name);

            $parent = $parent
                ->with($view->context())
                ->with(
                    '__content',
                    new ChildContent(function () use ($view): void {
                        $this->requireView($view);
                    })
                );

            $this->renderView($parent);

            return;
        }

        $this->requireView($view);
    }

    private function requireView(View $view): void
    {
        $this->finder->includeFile($view->path(), $view->context());
    }

    /**
     * @throws ViewCantBeRendered
     *
     * @return never
     */
    private function handleViewException(Throwable $e, int $ob_level, View $view): void
    {
        while (ob_get_level() > $ob_level) {
            OutputBuffer::remove();
        }

        throw ViewCantBeRendered::fromPrevious($view->name(), $e);
    }

    private function parseParentName(View $view): ?string
    {
        $path = (string)$view->path();
        $data = file_get_contents($path, false, null, 0, 100);

        if (false === $data) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('Cant read file contents of view [%s].', $path));
            // @codeCoverageIgnoreEnd
        }

        $scope = Str::betweenFirst($data, '/*', '*/');

        $pattern = sprintf('#(?:%s:\s?)(.+)#', self::PARENT_FILE_INDICATOR);

        $match = preg_match($pattern, $scope, $matches);

        if (false === $match) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException(sprintf('preg_match failed on string [%s]', $scope));
            // @codeCoverageIgnoreEnd
        }

        if (0 === $match) {
            return null;
        }

        if (!isset($matches[1])) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $match = str_replace(' ', '', $matches[1]);

        if ('' === $match) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        return $match;
    }

}
