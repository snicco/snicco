<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Throwable;

/**
 * @api
 */
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
     * @interal
     * @throws ViewCantBeRendered
     */
    public function renderPhpView(PHPView $view): string
    {
        $ob_level = ob_get_level();

        ob_start();

        try {
            $this->render($view);
        } catch (Throwable $e) {
            $this->handleViewException($e, $ob_level, $view);
        }

        return ltrim(ob_get_clean());
    }

    /**
     * @return void
     */
    private function render(PHPView $view)
    {
        $this->composer_collection->compose($view);

        if ($view->parent() !== null) {
            $view->parent()
                ->with(
                    array_filter($view->context(), function ($value) {
                        return !$value instanceof ChildContent;
                    })
                )
                ->with(
                    '__content',
                    new ChildContent(function () use ($view) {
                        $this->requireView($view);
                    })
                );

            $this->render($view->parent());

            return;
        }

        $this->requireView($view);
    }

    private function requireView(PHPView $view): void
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
    private function handleViewException(Throwable $e, $ob_level, PHPView $view)
    {
        while (ob_get_level() > $ob_level) {
            ob_end_clean();
        }

        throw ViewCantBeRendered::fromPrevious($view->name(), $e);
    }

}
