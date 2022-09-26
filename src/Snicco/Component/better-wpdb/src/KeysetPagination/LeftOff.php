<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPDB\KeysetPagination;

/**
 * @psalm-readonly
 */
final class LeftOff
{
    /**
     * @var array<string,scalar|null>
     */
    public array $last_included_record_sorting_values;

    /**
     * @param array<string,scalar|null> $last_included_record_sorting_values
     */
    public function __construct(array $last_included_record_sorting_values)
    {
        $this->last_included_record_sorting_values = $last_included_record_sorting_values;
    }
}
