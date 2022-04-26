<?php

declare(strict_types=1);

namespace Snicco\Component\Session\Serializer;

use RuntimeException;

use function is_array;
use function serialize;
use function unserialize;

final class PHPSerializer implements Serializer
{
    public function serialize(array $session_data): string
    {
        return serialize($session_data);
    }

    public function deserialize(string $data): array
    {
        $res = @unserialize($data);

        if (false === $res) {
            throw new RuntimeException('Could not unserialize session content in ' . self::class);
        }

        if (! is_array($res)) {
            throw new RuntimeException('Invalid session content. Unserialized session data is not an array.');
        }

        return $res;
    }
}
