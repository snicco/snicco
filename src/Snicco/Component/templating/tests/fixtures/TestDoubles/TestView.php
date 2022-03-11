<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests\fixtures\TestDoubles;

use BadMethodCallException;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\View\View;

use function is_array;
use function sprintf;

class TestView implements View
{
    /**
     * @var array<string,mixed>
     */
    private array $context = [];

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param array<string, mixed>|string $key
     * @param mixed                       $value
     *
     * @return static
     *
     * @psalm-mutation-free
     */
    public function with($key, $value = null): View
    {
        $new = clone $this;
        if (is_array($key)) {
            $new->context = array_merge($this->context(), $key);
        } else {
            $new->context[$key] = $value;
        }

        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function context(): array
    {
        return $this->context;
    }

    public function render(): string
    {
        throw new BadMethodCallException(sprintf('Test double [%s] can not be rendered to string.', self::class));
    }

    /**
     * @psalm-mutation-free
     */
    public function path(): string
    {
        throw new BadMethodCallException('TestView.php has no path.');
    }

    /**
     * @psalm-mutation-free
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @psalm-mutation-free
     */
    public function parent(): ?PHPView
    {
        return null;
    }
}
