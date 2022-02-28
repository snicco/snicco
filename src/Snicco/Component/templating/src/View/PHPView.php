<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\View;

use RuntimeException;
use Snicco\Component\StrArr\Str;
use Snicco\Component\Templating\ViewFactory\PHPViewFactory;

/**
 * @psalm-internal Snicco\Component\Templating
 */
final class PHPView implements View
{

    /**
     * Name of view file header based on which to resolve parent views.
     *
     * @var string
     */
    public const PARENT_FILE_INDICATOR = 'Extends';

    private PHPViewFactory $engine;

    private ?PHPView $parent_view;

    private string $filepath;

    /**
     * @var array<string,mixed>
     */
    private array $context = [];

    private string $name;

    public function __construct(PHPViewFactory $engine, string $name, string $path)
    {
        $this->engine = $engine;
        $this->name = $name;
        $this->filepath = $path;
        $this->parent_view = $this->parseParentView();
    }

    public function path(): string
    {
        return $this->filepath;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function render(): string
    {
        return $this->engine->renderPhpView($this);
    }

    /**
     * @param string|array<string, mixed> $key
     * @param mixed $value
     */
    public function addContext($key, $value = null): void
    {
        if (is_array($key)) {
            $this->context = array_merge($this->context(), $key);
        } else {
            $this->context[$key] = $value;
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function withContext(array $context): void
    {
        $this->context = $context;
    }

    public function parent(): ?PHPView
    {
        return $this->parent_view;
    }

    /**
     * Create a view instance for the given view's layout header, if any.
     *
     * @return PHPView|null
     */
    private function parseParentView(): ?PHPView
    {
        $parent_view_name = $this->parseExtends();

        if (null === $parent_view_name) {
            return null;
        }

        return $this->engine->make($parent_view_name);
    }

    private function parseExtends(): ?string
    {
        $data = file_get_contents($this->filepath, false, null, 0, 100);

        if (false === $data) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("Cant read file contents of view [$this->filepath].");
            // @codeCoverageIgnoreEnd
        }

        $scope = Str::betweenFirst($data, '/*', '*/');

        $match = preg_match('/(?:Extends:\s?)(.+)/', $scope, $matches);

        if (false === $match) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException("preg_match failed on string [$scope]");
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
