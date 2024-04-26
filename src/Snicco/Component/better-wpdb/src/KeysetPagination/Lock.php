<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\KeysetPagination;

/**
 * @psalm-immutable
 */
final class Lock
{
    /**
     * @var "lock in share mode"|"for update"
     *
     * @interal
     *
     * @psalm-internal Snicco\Component\BetterWPDB
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
        // "for share" is not compatible with MariaDB while for "lock in share mode" is compatible with both.
        return new self('lock in share mode');
    }

    public static function forReadWrite(): self
    {
        return new self('for update');
    }
}
