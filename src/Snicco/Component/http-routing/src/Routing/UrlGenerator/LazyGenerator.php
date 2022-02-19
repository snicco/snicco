<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\UrlGenerator;

use Closure;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class LazyGenerator implements UrlGenerator
{
    private ?UrlGenerator $generator = null;

    /**
     * @var Closure():UrlGenerator
     */
    private Closure $get_generator;

    /**
     * @param Closure():UrlGenerator $get_generator
     */
    public function __construct(Closure $get_generator)
    {
        $this->get_generator = $get_generator;
    }

    public function to(string $path, array $extra = [], int $type = self::ABSOLUTE_PATH, ?bool $secure = null): string
    {
        return $this->lazyGenerator()->to($path, $extra, $type, $secure);
    }

    public function toRoute(
        string $name,
        array $arguments = [],
        int $type = self::ABSOLUTE_PATH,
        ?bool $secure = null
    ): string {
        return $this->lazyGenerator()->toRoute($name, $arguments, $type, $secure);
    }

    public function toLogin(array $arguments = [], int $type = self::ABSOLUTE_PATH): string
    {
        return $this->lazyGenerator()->toLogin($arguments, $type);
    }

    public function secure(string $path, array $extra = []): string
    {
        return $this->lazyGenerator()->secure($path, $extra);
    }

    public function canonical(): string
    {
        return $this->lazyGenerator()->canonical();
    }

    public function full(): string
    {
        return $this->lazyGenerator()->full();
    }

    public function previous(string $fallback = '/'): string
    {
        return $this->lazyGenerator()->previous($fallback);
    }

    private function lazyGenerator(): UrlGenerator
    {
        if (!isset($this->generator)) {
            $get_generator = $this->get_generator;
            $this->generator = $get_generator();
        }
        return $this->generator;
    }
}