<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\KeysetPagination;

/**
 * @psalm-immutable
 */
final class Lock
{
    /**
     * @var "for share"|"for update"
     */
    public string $type;

    /**
     * @param "for share"|"for update" $type
     */
    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function forRead(): self
    {
        return new self('for share');
    }

    public static function forReadWrite(): self
    {
        return new self('for update');
    }
}
