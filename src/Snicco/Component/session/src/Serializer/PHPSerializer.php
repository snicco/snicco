<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Serializer;

use RuntimeException;

use function is_array;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function unserialize;

final class PHPSerializer implements Serializer
{
    public function serialize(array $session_data): string
    {
        return serialize($session_data);
    }

    public function deserialize(string $data): array
    {
        set_error_handler(function (int $code, string $message) {
            throw new RuntimeException(
                'Could not unserialize session content in ' . __CLASS__ . "\nMessage: " . $message,
                $code
            );
        });

        $res = unserialize($data);
        if (! is_array($res)) {
            throw new RuntimeException('Invalid session content. Unserialized session data is not an array.');
        }

        restore_error_handler();

        return $res;
    }
}
