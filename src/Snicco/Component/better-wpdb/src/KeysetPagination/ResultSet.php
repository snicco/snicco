<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\KeysetPagination;

use Countable;

use function count;

/**
 * @psalm-readonly
 */
final class ResultSet implements Countable
{
    /**
     * @var list<array<string,scalar|null>>
     */
    public array $records;

    public LeftOff $left_off;

    public bool $is_last;

    /**
     * @param list<array<string,scalar|null>> $records
     */
    private function __construct(array $records, LeftOff $left_off, bool $is_last)
    {
        $this->records = $records;
        $this->left_off = $left_off;
        $this->is_last = $is_last;
    }

    public static function empty(): self
    {
        return new self(
            [],
            new LeftOff([]),
            true
        );
    }

    /**
     * @param list<array<string,scalar|null>> $records
     */
    public static function fromRecords(array $records, LeftOff $left_off, bool $is_last): self
    {
        return new self($records, $left_off, $is_last);
    }

    public function count(): int
    {
        return count($this->records);
    }
}
