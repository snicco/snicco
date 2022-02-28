<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\Tests\fixtures\TestDoubles;

use BadMethodCallException;
use Snicco\Component\Templating\View\PHPView;
use Snicco\Component\Templating\View\View;

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


    public function addContext($key, $value = null): void
    {
        if (is_array($key)) {
            $this->context = array_merge($this->context(), $key);
        } else {
            $this->context[$key] = $value;
        }
    }

    public function context(): array
    {
        return $this->context;
    }

    public function render(): string
    {
        throw new BadMethodCallException(sprintf('Test double [%s] can not be rendered to string.', self::class));
    }

    public function path(): string
    {
        throw new BadMethodCallException('TestView.php has no path.');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function withContext(array $context): void
    {
        $this->context = $context;
    }

    public function parent(): ?PHPView
    {
        return null;
    }
}