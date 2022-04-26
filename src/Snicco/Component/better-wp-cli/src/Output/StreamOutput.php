<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Output;

use InvalidArgumentException;
use Snicco\Component\BetterWPCLI\Check;
use Snicco\Component\BetterWPCLI\Verbosity;

use function fflush;
use function function_exists;
use function fwrite;
use function getenv;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

final class StreamOutput extends OutputWithVerbosity
{
    /**
     * @var resource
     */
    private $stream;

    /**
     * @param resource $stream
     */
    public function __construct($stream, int $verbosity = Verbosity::NORMAL, bool $decorated = null)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (! Check::isStream($stream)) {
            throw new InvalidArgumentException(sprintf('%s needs a stream as its first argument.', self::class,));
        }

        $this->stream = $stream;

        if (null === $decorated) {
            $decorated = $this->hasColorSupport();
        }

        parent::__construct($verbosity, $decorated);
    }

    /**
     * @interal
     *
     * @psalm-internal Snicco\Component\BetterWPCLI\Output
     */
    public function writeStream(string $message, bool $newline): void
    {
        if ($newline) {
            $message .= PHP_EOL;
        }

        fwrite($this->stream, $message);
        fflush($this->stream);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->writeStream($message, $newline);
    }

    // Reference: https://github.com/symfony/console/blob/5.4/Output/StreamOutput.php#L94
    private function hasColorSupport(): bool
    {
        // See https://no-color.org/
        if (isset($_SERVER['NO_COLOR'])) {
            return false;
        }

        if (false !== getenv('NO_COLOR')) {
            return false;
        }

        if ('Hyper' === getenv('TERM_PROGRAM')) {
            return true;
        }

        // @codeCoverageIgnoreStart
        if (DIRECTORY_SEPARATOR === '\\') {
            return (function_exists('sapi_windows_vt100_support')
                    && @sapi_windows_vt100_support($this->stream))
                   || false !== getenv('ANSICON')
                   || 'ON' === getenv('ConEmuANSI')
                   || 'xterm' === getenv('TERM');
        }

        return stream_isatty($this->stream);
        // @codeCoverageIgnoreEnd
        // This is untestable in GitHub actions. STDOUT is never a tty.
    }
}
