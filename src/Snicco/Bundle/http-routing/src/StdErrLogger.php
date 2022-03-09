<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use DateTimeInterface;
use Psr\Log\AbstractLogger;
use Throwable;

use function count;
use function error_log;
use function explode;
use function get_class;
use function gettype;
use function implode;
use function is_null;
use function is_object;
use function is_scalar;
use function is_string;
use function method_exists;
use function rtrim;
use function sprintf;
use function strpos;
use function strtoupper;
use function strtr;
use function strval;

use const PHP_EOL;

/**
 * A minimal PSR-3 Logger that will log all messages to stderr (error_log).
 *
 * @psalm-internal Snicco\Bundle\HttpRouting
 *
 * @internal
 */
final class StdErrLogger extends AbstractLogger
{
    private string $channel;

    public function __construct(string $channel = 'request')
    {
        $this->channel = $channel;
    }

    public function log($level, $message, array $context = [])
    {
        $level = (string) $level;

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
            if (strpos($message, "{{$key}}") !== false) {
                $replacements["{{$key}}"] = $this->valueToString($value);
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

        if (count($replacements)) {
            $message = strtr($message, $replacements);
        }

        if (count($additional)) {
            $message .= "\n\tContext: [";

            foreach ($additional as $key => $value) {
                if (is_string($key)) {
                    $message .= "'$key' => " . $value;
                } else {
                    $message .= $value;
                }
                $message .= ', ';
            }
            $message = rtrim($message, ', ');
            $message .= ']';
        }

        if ($exception) {
            $exception_string = $this->formatException($exception);
        } else {
            $exception_string = '';
        }

        $entry = sprintf('%s %s %s', $this->channel . '.' . strtoupper($level), $message, $exception_string);

        error_log($entry . PHP_EOL);
    }

    /**
     * @param mixed $value
     */
    private function valueToString($value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        } elseif ($value instanceof DateTimeInterface) {
            return $value->format('d-M-Y H:i:s e');
        } elseif (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            return 'object ' . get_class($value);
        }
        return gettype($value);
    }

    private function formatException(Throwable $exception): string
    {
        $previous = $exception->getPrevious();

        $message = $this->exceptionToString($exception, is_null($previous));

        if ($previous instanceof Throwable) {
            $message .= "\n\tCaused by: " . $this->exceptionToString($previous, true);
        }

        return "\n\t" . $message;
    }

    private function exceptionToString(Throwable $e, bool $include_trace): string
    {
        $message = get_class($e) .
            ' "' . $e->getMessage() . '"' .
            ' in ' . $e->getFile() .
            ':' . strval($e->getLine());

        if ($include_trace) {
            $exploded = explode('#', $e->getTraceAsString());

            $trace = implode("\t#", $exploded);

            $message .= "\n\tStack trace: \n" . $trace;
        }
        return $message;
    }
}
