<?php

declare(strict_types=1);

namespace Snicco\Component\Templating\ValueObject;

use Snicco\Component\Templating\Exception\InvalidFile;

use function is_file;
use function sprintf;

final class FilePath
{
    private string $file;

    private function __construct(string $file)
    {
        if (! is_file($file)) {
            throw new InvalidFile(sprintf('%s is not a valid file', $file));
        }

        $this->file = $file;
    }

    public function __toString(): string
    {
        return $this->file;
    }

    public static function fromString(string $file): FilePath
    {
        return new self($file);
    }
}
