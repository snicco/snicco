<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\OutputBuffer;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Throwable;

use function array_filter;

final class PHPViewFactory implements ViewFactory
{
    private PHPViewFinder $finder;

    private ViewComposerCollection $composer_collection;

    public function __construct(PHPViewFinder $finder, ViewComposerCollection $composers)
    {
        $this->finder = $finder;
        $this->composer_collection = $composers;
    }

    public function make(string $view): PHPView
    {
        return new PHPView(
            $this,
            $view,
            $this->finder->filePath($view)
        );
    }

    /**
     * @throws ViewCantBeRendered
     *
     * @psalm-internal Snicco\Component\Templating
     */
    public function renderPhpView(PHPView $view): string
    {
        $ob_level = ob_get_level();

        OutputBuffer::start();

        try {
            $this->render($view);
        } catch (Throwable $e) {
            $this->handleViewException($e, $ob_level, $view);
        }

        return ltrim(OutputBuffer::get());
    }

    private function render(PHPView $view): void
    {
        $view = $this->composer_collection->compose($view);

        $parent = $view->parent();

        if (null !== $parent) {
            $parent = $parent
                ->with(
                    array_filter($view->context(), function ($value) {
                        return ! $value instanceof ChildContent;
                    })
                )
                ->with(
                    '__content',
                    new ChildContent(function () use ($view) {
                        $this->requireView($view);
                    })
                );

            $this->render($parent);

            return;
        }

        $this->requireView($view);
    }

    private function requireView(View $view): void
    {
        $this->finder->includeFile(
            $view->path(),
            $view->context()
        );
    }

    /**
     * @throws ViewCantBeRendered
     *
     * @return never
     */
    private function handleViewException(Throwable $e, int $ob_level, PHPView $view)
    {
        while (ob_get_level() > $ob_level) {
            OutputBuffer::remove();
        }

        throw ViewCantBeRendered::fromPrevious($view->name(), $e);
    }
}
