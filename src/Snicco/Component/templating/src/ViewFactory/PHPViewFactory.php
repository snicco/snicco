<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ViewFactory;

use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\Context\ViewContextResolver;
use Snicco\Component\Templating\Exception\ViewCantBeRendered;
use Snicco\Component\Templating\OutputBuffer;
use Snicco\Component\Templating\ValueObject\View;
use Throwable;

use Webmozart\Assert\Assert;

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

    private ViewContextResolver $composer_collection;

    private int $content_parse_length;

    /**
     * @param list<string> $view_directories
     * @param int          $parent_view_parse_length The length of the view content that will be used to parse a parent view
     */
    public function __construct(
        ViewContextResolver $composers,
        array $view_directories,
        int $parent_view_parse_length = 100
    ) {
        $this->finder = new PHPViewFinder($view_directories);
        $this->composer_collection = $composers;
        $this->content_parse_length = $parent_view_parse_length;
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
                ->with('__content', new ChildContent(function () use ($view): void {
                    $this->requireView($view);
                }));

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
        $path = (string) $view->path();
        $data = file_get_contents($path, false, null, 6, $this->content_parse_length);

        Assert::string($data, sprintf('file_get_contents returned false for path [%s].', $path));

        $scope = Str::betweenFirst($data, '/*', '*/');

        $pattern = sprintf('#(?:%s:\s?)(.+)#', self::PARENT_FILE_INDICATOR);

        $match = preg_match($pattern, $scope, $matches);

        Assert::notFalse($match, sprintf('preg_match failed on string [%s]', $scope));

        if (0 === $match) {
            return null;
        }

        Assert::true(isset($matches[1]), 'preg_match assigned variable is missing key "1". This should never happen.');

        return str_replace(' ', '', $matches[1]);
    }
}
