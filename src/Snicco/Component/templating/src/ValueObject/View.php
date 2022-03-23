<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ValueObject;

use Snicco\Component\Templating\ViewFactory\ViewFactory;

use function array_replace;
use function is_array;

/**
 * A view is an immutable data transfer object that later be rendered to a string.
 * An instantiated View can ALWAYS be rendered by using {@see TemplateEngine::renderView()}
 *
 * @psalm-immutable
 */
final class View
{

    private string $name;

    private FilePath $file_path;

    /**
     * @var array<string,mixed>
     */
    private array $context;

    /**
     * @var class-string<ViewFactory>
     */
    private string $view_factory;

    /**
     * @param class-string<ViewFactory> $view_factory
     * @param array<string,mixed> $context
     */
    public function __construct(string $name, FilePath $file_path, string $view_factory, array $context = [])
    {
        $this->name = $name;
        $this->file_path = $file_path;
        $this->view_factory = $view_factory;
        $this->context = $context;
    }

    /**
     * Takes the provided context and returns a NEW instance that now has the
     * merged context.
     *
     * @param array<string, mixed>|string $key
     * @param mixed $value
     */
    public function with($key, $value = null): self
    {
        $new = clone $this;
        if (is_array($key)) {
            $new->context = array_replace($this->context, $key);
        } else {
            $new->context[$key] = $value;
        }

        return $new;
    }

    /**
     * @return array<string,mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): FilePath
    {
        return $this->file_path;
    }

    /**
     * @internal
     *
     * @return class-string<ViewFactory>
     *
     * @psalm-internal Snicco\Component\Templating
     */
    public function viewFactoryClass(): string
    {
        return $this->view_factory;
    }
}
