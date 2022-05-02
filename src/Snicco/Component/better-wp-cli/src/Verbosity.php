<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI;

final class Verbosity
{
    /**
     * @var int
     */
    public const QUIET = 16;

    /**
     * @var int
     */
    public const NORMAL = 32;

    /**
     * @var int
     */
    public const VERBOSE = 64;

    /**
     * @var int
     */
    public const VERY_VERBOSE = 128;

    /**
     * @var int
     */
    public const DEBUG = 256;
}
