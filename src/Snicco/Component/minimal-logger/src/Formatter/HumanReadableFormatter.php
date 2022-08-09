<?php

declare(strict_types=1);

namespace Snicco\Component\MinimalLogger\Formatter;

use DateTimeInterface;
use Throwable;

use function array_replace;
use function count;
use function explode;
use function get_class;
use function gettype;
use function implode;
use function is_object;
use function is_scalar;
use function is_string;
use function method_exists;
use function rtrim;
use function sprintf;
use function strpos;
use function strtoupper;

final class HumanReadableFormatter implements Formatter
{
    public const HIDE_STACK_TRACE_ARGS = 'hide_stack_trace_args';

    /**
     * @var array{
     *     self::HIDE_STACK_TRACE_ARGS: bool,
     * }
     */
    private array $options;

    /**
     * @param  array{
     *     self::HIDE_STACK_TRACE_ARGS?: bool,
     * }  $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_replace([
            self::HIDE_STACK_TRACE_ARGS => true,
        ], $options);
    }

    public function format(string $level, string $message, array $context = [], string $line_prefix = ''): string
    {
        /** @var array<string> $additional */
        $additional = [];

        /** @var array<string,string> $replacements */
        $replacements = [];

        /** @var Throwable|null $exception */
        $exception = null;

        /**
         * @var mixed $value
         */
        foreach ($context as $key => $value) {
            if (false !== strpos($message, sprintf('{%s}', $key))) {
                $replacements[sprintf('{%s}', $key)] = $this->valueToString($value);

                continue;
            }

            if ('exception' === $key && $value instanceof Throwable) {
                $exception = $value;

                continue;
            }

            $value = is_string($value)
                ? "'" . $this->valueToString($value) . "'"
                : $this->valueToString($value);

            $additional[$key] = $value;
        }

        if ([] !== $replacements) {
            $message = strtr($message, $replacements);
        }

        if ([] !== $additional) {
            $message .= "\n\tContext: [";

            foreach ($additional as $key => $value) {
                if (is_string($key)) {
                    $message .= sprintf("'%s' => ", $key) . $value;
                } else {
                    $message .= $value;
                }

                $message .= ', ';
            }

            $message = rtrim($message, ', ') . ']';
        }

        $exception_string = null !== $exception ? $this->formatException($exception) : '';

        return sprintf('%s %s %s', $line_prefix . strtoupper($level), $message, $exception_string);
    }

    /**
     * @param mixed $value
     */
    private function valueToString($value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('d-M-Y H:i:s e');
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return 'object ' . get_class($value);
        }

        return gettype($value);
    }

    private function formatException(Throwable $e): string
    {
        $message = $this->exceptionToString($e, true);

        if ($previous = $e->getPrevious()) {
            do {
                $message .= "\n\tCaused by: " . $this->exceptionToString($previous, true);
            } while ($previous = $previous->getPrevious());
        }

        return "\n\t" . $message;
    }

    private function exceptionToString(Throwable $e, bool $include_trace): string
    {
        $message = sprintf(
            '%s "%s" in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        if ($include_trace) {
            $message .= "\n\tStack trace:\n\t";
            $message .= implode("\n\t", $this->formatTrace($e));
        }

        return $message;
    }

    private function formatTrace(Throwable $e): array
    {
        if (! $this->options[self::HIDE_STACK_TRACE_ARGS]) {
            $string_trace = $e->getTraceAsString();

            return explode("\n", $string_trace);
        }

        $traces = [];

        foreach ($e->getTrace() as $index => $frame) {
            $trace = sprintf('#%d %s(%s):', $index, $frame['file'], $frame['line']);

            if (isset($frame['class'], $frame['type'], $frame['function'])) {
                $trace .= $frame['class'] . $frame['type'] . $frame['function'];
            } else {
                $trace .= $frame['function'];
            }

            if (isset($frame['args']) && count($frame['args'])) {
                $trace .= '(REDACTED)';
            } else {
                $trace .= '()';
            }
            $traces[] = $trace;
        }

        return $traces;
    }
}
