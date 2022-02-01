<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use RuntimeException;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\ViewComposer\ViewComposerCollection;
use Throwable;

use function ob_get_clean;

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
     * @throws RuntimeException If output buffering can't be enabled. This should never happen.
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

        $output = ob_get_clean();

        if (false === $output) {
            throw new RuntimeException('Output buffering was not enabled. This should not be happen.');
        }

        return ltrim($output);
    }

    private function render(PHPView $view): void
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
     * @return never
     * @throws ViewCantBeRendered
     *
     */
    private function handleViewException(Throwable $e, int $ob_level, PHPView $view)
    {
        while (ob_get_level() > $ob_level) {
            ob_end_clean();
        }

        throw ViewCantBeRendered::fromPrevious($view->name(), $e);
    }

}
